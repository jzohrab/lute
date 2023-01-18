<?php

namespace App\Repository;

use App\Entity\Text;
use App\Entity\Term;
use App\Entity\Language;
use App\Repository\TermRepository;
use App\Utils\Connection;


class TextItemRepository {

    /** PUBLIC **/

    /** Map all matching TextItems in a Text to all saved Terms. */
    public static function mapForText(Text $text) {
        $eu = new TextItemRepository();
        $eu->map_by_textlc($text->getLanguage(), $text);
        $eu->add_multiword_terms_for_text($text);
    }

    /** Map all matching TextItems to a Term. */
    public static function mapForTerm(Term $term) {
        if ($term->getTextLC() != null && $term->getID() == null)
            throw new \Exception("Term {$term->getTextLC()} is not saved.");
        $eu = new TextItemRepository();
        $eu->map_textitems_for_term($term);
        $p = $term->getParent();
        if ($p != null) {
            $eu->map_textitems_for_term($p);
        }
    }

    /** Bulk map. */
    public static function bulkMap(array $terms) {
        // First pass: map exact string matches.
        $eu = new TextItemRepository();
        $eu->map_by_textlc();

        $mword_terms = array_filter($terms, fn($t) => ($t->getWordCount() > 1));
        foreach ($mword_terms as $term) {
            $eu->add_multiword_textitems(
                $term->getTextLC(),
                $term->getLanguage(),
                $term->getID(),
                $term->getWordCount()
            );
        }
    }

    /** Break any TextItem-Term mappings for the Term. */
    public static function unmapForTerm(Term $term) {
        if ($term->getTextLC() != null && $term->getID() == null)
            throw new \Exception("Term {$term->getTextLC()} is not saved.");
        $eu = new TextItemRepository();
        $eu->unmap_all($term);
        $p = $term->getParent();
        if ($p != null) {
            $eu->unmap_all($p);
        }
    }

    /** Map all TextItems in *this text* that match the TextLC of saved Terms. */
    public static function mapStringMatchesForText(Text $text) {
        $eu = new TextItemRepository();
        $eu->map_by_textlc($text->getLanguage(), $text);
    }

    /** Map all TextItems in *this and other texts* that match the
     * TextLC of saved Terms in this Text. */
    public static function mapStringMatchesForLanguage(Language $lang) {
        $eu = new TextItemRepository();
        $eu->map_by_textlc($lang);
    }


    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getFromEnvironment();
    }

    /** PRIVATE **/

    private function exec_sql($sql, $params = null) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception($this->conn->error);
        }
        if ($params) {
            $stmt->bind_param(...$params);
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        return $stmt->get_result();
    }


    private function map_by_textlc(
        ?Language $lang = null,
        ?Text $text = null,
        ?Term $term = null
    ) {
        $sql = "update textitems2
inner join words on ti2textlc = wotextlc and ti2lgid = wolgid
set ti2woid = woid
where ti2woid = 0";
        if ($lang != null) {
            $lid = $lang->getLgID();
            $sql .= " AND ti2lgid = {$lid}";
        }
        if ($text != null) {
            $tid = $text->getID();
            $sql .= " AND ti2TxID = {$tid}";
        }
        if ($term != null) {
            $wid = $term->getID();
            $sql .= " AND WoID = {$wid}";
        }
        $this->exec_sql($sql);
    }

    private function add_multiword_terms_for_text(Text $text)
    {
        $id = $text->getID();
        $lid = $text->getLanguage()->getLgID();
        $minmax = "SELECT MIN(SeID) as minseid, MAX(SeID) as maxseid FROM sentences WHERE SeTxID = {$id}";
        $rec = $this->conn
             ->query($minmax)->fetch_array();
        $firstSeID = intval($rec['minseid']);
        $lastSeID = intval($rec['maxseid']);
    
        // For each expession in the language, add expressions for the
        // sentence range.  Inefficient, but for now I don't care --
        // will see how slow it is.  Note it's not useful to limit it
        // to multi-word terms that aren't in the text, since _most_
        // of them won't be in the text.  The only useful check would
        // be for new things added since the last parse date, which
        // currently isn't tracked.
        $sentenceRange = [ $firstSeID, $lastSeID ];
        $mwordsql = "SELECT WoTextLC, WoID, WoWordCount FROM words WHERE WoLgID = $lid AND WoWordCount > 1";
        $res = $this->conn->query($mwordsql);
        while ($record = mysqli_fetch_assoc($res)) {
            $this->add_multiword_textitems(
                $record['WoTextLC'],
                $text->getLanguage(),
                $record['WoID'],
                $record['WoWordCount'],
                $sentenceRange);
        }
        mysqli_free_result($res);
    }


    private function map_textitems_for_term(Term $term)
    {
        if ($term->getWordCount() == 1) {
            $this->map_by_textlc($term->getLanguage(), null, $term);
        }
        else {
            $this->add_multiword_textitems(
                $term->getTextLC(),
                $term->getLanguage(),
                $term->getID(),
                $term->getWordCount()
            );
        }
    }

    private function unmap_all(Term $term)
    {
        $woid = $term->getID();
        $sql = "UPDATE textitems2 SET Ti2WoID = 0 WHERE Ti2WoID = {$woid}";

        if ($term->getWordCount() > 1) {
            // add_multiword_textitems_for_sentences adds new
            // textitem2 records for multiword terms, so if a
            // multiword term is removed, we need to remove those
            // records.
            // "Ti2WordCount > 1" is a sanity-check condition only.
            $sql = "DELETE FROM textitems2 WHERE Ti2WoID = {$woid} and Ti2WordCount > 1";
        }

        $this->exec_sql($sql);
    }

    /** Expressions **************************/


    // Note that sentence range feels redundant, but is used elsewhere when new expr defined and ll texts in language have to be updated.
    /**
     * @param string $textlc Text in lower case
     * @param Language the language
     * @param string $wordcount
     * @param array  $sentenceIDRange   [ lower SeID, upper SeID ] to consider.
     */
    private function add_multiword_textitems(
        $textlc, Language $lang, $wid, $wordcount, $sentenceIDRange = NULL
    )
    {
        if ($wordcount < 2) {
            throw new \Exception("Only call this for multi-word terms.");
        }

        $splitEachChar = $lang->isLgSplitEachChar();
        if ($splitEachChar) {
            $textlc = preg_replace('/([^\s])/u', "$1 ", $textlc);
        }

        $sentences = $this->get_sentences_containing_textlc($lang, $textlc, $sentenceIDRange);
        $this->add_multiword_textitems_for_sentences(
            $sentences, $lang, $textlc, $wid, $wordcount
        );
    }
    
    
    // Ref https://stackoverflow.com/questions/1725227/preg-match-and-utf-8-in-php
    
    /**
     * Returns array of matches in same format as preg_match or preg_match_all
     * @param bool   $matchAll If true, execute preg_match_all, otherwise preg_match
     * @param string $pattern  The pattern to search for, as a string.
     * @param string $subject  The input string.
     * @param int    $offset   The place from which to start the search (in bytes).
     * @return array
     */
    private function pregMatchCapture($matchAll, $pattern, $subject, $offset = 0)
    {
        if ($offset != 0) { $offset = strlen(mb_substr($subject, 0, $offset)); }
        
        $matchInfo = array();
        $method    = 'preg_match';
        $flag      = PREG_OFFSET_CAPTURE;
        if ($matchAll) {
            $method .= '_all';
        }

        $n = $method($pattern, $subject, $matchInfo, $flag, $offset);

        $result = array();
        if ($n !== 0 && !empty($matchInfo)) {
            if (!$matchAll) {
                $matchInfo = array($matchInfo);
            }
            foreach ($matchInfo as $matches) {
                $positions = array();
                foreach ($matches as $match) {
                    $matchedText   = $match[0];
                    $matchedLength = $match[1];
                    // dump($subject);
                    $positions[]   = array(
                        $matchedText,
                        mb_strlen(mb_strcut($subject, 0, $matchedLength))
                    );
                }
                $result[] = $positions;
            }
            if (!$matchAll) {
                $result = $result[0];
            }
        }
        return $result;
    }


    private function get_sentences_containing_textlc(
        Language $lang, $textlc, $sentenceIDRange
    ) {

        $lid = $lang->getLgID();
        $removeSpaces = $lang->isLgRemoveSpaces();
        $splitEachChar = $lang->isLgSplitEachChar();
        $termchar = $lang->getLgRegexpWordCharacters();

        $whereSeIDRange = '';
        if (! is_null($sentenceIDRange)) {
            [ $lower, $upper ] = $sentenceIDRange;
            $whereSeIDRange = "(SeID >= {$lower} AND SeID <= {$upper}) AND";
        }

        $sql = "SELECT * FROM sentences 
            WHERE {$whereSeIDRange}
            SeLgID = $lid AND SeText LIKE concat('%', ?, '%')";
        $params = [ 's', mysqli_real_escape_string($this->conn, $textlc) ];

        // $countsql = "select count(*) as c from ($sql) src";
        // $count = $this->exec_sql($countsql, $params);
        // $record = mysqli_fetch_assoc($count);
        // $c = $record['c'];
        // mysqli_free_result($count);
        // dump("got $c sentences matching \"{$textlc}\"");

        $res = $this->exec_sql($sql, $params);
        $result = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $string = ' ' . $record['SeText'] . ' ';
            $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
            if ($last_pos !== false)
                $result[] = $record;
        }
        mysqli_free_result($res);
        return $result;
    }


    /**
     * @param string $textlc Text to insert in lower case
     * @param string $lid    Language ID
     * @param int    $wid    Word ID of the expression
     * @param array  $sentenceIDRange
     */
    private function add_multiword_textitems_for_sentences(
        $sentences, Language $lang, $textlc, $wid, $wordcount
    )
    {
        $lid = $lang->getLgID();
        $termchar = $lang->getLgRegexpWordCharacters();
        $zws = mb_chr(0x200B);
        $searchre = "/{$zws}({$textlc}){$zws}/ui";

        // Sentences for Languages that don't have spaces are stored
        // with zero-width-spaces between each token.
        if ($lang->isLgRemoveSpaces()) {
            $searchre = "/({$textlc})/ui";
        }

        foreach ($sentences as $record) {
            // TODO:badcomment?
            // These spaces are required, otherwise the regex $serchre
            // doesn't find a string match right at the start of sentences,
            // since it has the [^$termchar] at the start of the pattern.
            $string = $record['SeText'];
            $firstpos = $record['SeFirstPos'];

            $allmatches = $this->pregMatchCapture(true, $searchre, $string);
            $termmatches = [];
            if (count($allmatches) > 0)
                $termmatches = $allmatches[1];
            // dump($termmatches);
            // Sample $termmatches data:
            // array(3) { [0]=> array(2) { [0]=> string(7) "Un gato", [1]=> int(2) }, ... }

            foreach($termmatches as $tm) {
                $cnt = $this->get_term_count_before($string, $tm[1], $lang);
                // $pos = 2 * $cnt + (int) $firstpos;
                // if ($lang->isLgRemoveSpaces()) {
                    $pos = $cnt + (int) $firstpos;
                // }
                // dump('position to set for this = ' . $pos);
                $txt = $tm[0];

                $sql = "INSERT IGNORE INTO textitems2
                  (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = array(
                    "iiiiiis",
                    $wid, $lid, $record['SeTxID'], $record['SeID'], $pos, $wordcount, $txt);
                $this->exec_sql($sql, $params);

            } // end foreach termmatches

        }  // next sentence
    }


    private function get_term_count_before($string, $pos, $lang): int {
        dump('initial string: ' . $string);
        dump('getting count before, initial pos = ' . $pos);
        $beforesubstr = mb_substr($string, 0, $pos - 1, 'UTF-8');
        dump($beforesubstr);

        // TODO:move_to_language - a lot of the parsing/counting code
        // belongs in the Language, because the language has the
        // information necessary.  There's no need to get data from
        // the Language entity to do further calculations.
        // if ($lang->isLgRemoveSpaces()) {
            $zws = mb_chr(0x200B);
            $parts = explode($zws, $beforesubstr);
            dump('all parts:');
            dump($parts);
            $nonblank = array_filter($parts, fn($s) => mb_strlen($s) > 0);
            dump($nonblank);
            return count($nonblank);
            // }

            /*
        $termchar = $lang->getLgRegexpWordCharacters();
        $termsbefore = $this->pregMatchCapture(true, "/([$termchar]+)/u", $beforesubstr);
        if (count($termsbefore) != 0) {
            // dump($termsbefore[1]);
            return count($termsbefore[1]);
        }
        return 0;
            */
    }

}