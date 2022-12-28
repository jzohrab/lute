<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Term;
use App\Entity\Language;
use App\Repository\TermRepository;
use App\Utils\Connection;


// TODO:renameclass ... This is a bad name.  It's really a
// TextItem-related method, perhaps TextItemRepository is best.
// Then the method names could be associateFor[Text|Term].
class ExpressionUpdater {

    /** PUBLIC **/
    
    public static function associateExpressionsInText(Text $text) {
        $eu = new ExpressionUpdater();
        $eu->add_multiword_terms_for_text($text);
    }

    public static function associateTermTextItems(Term $term) {
        if ($term->getTextLC() != null && $term->getID() == null)
            throw new \Exception("Term {$term->getTextLC()} is not saved.");
        $eu = new ExpressionUpdater();
        $eu->associate_term_with_existing_texts($term);
        $p = $term->getParent();
        if ($p != null) {
            $eu->associate_term_with_existing_texts($p);
        }
    }

    public static function breakAll(Term $term) {
        if ($term->getTextLC() != null && $term->getID() == null)
            throw new \Exception("Term {$term->getTextLC()} is not saved.");
        $eu = new ExpressionUpdater();
        $eu->break_all($term);
        $p = $term->getParent();
        if ($p != null) {
            $eu->break_all($p);
        }
    }

    public static function associateAllExactMatches(?Text $text = null) {
        $eu = new ExpressionUpdater();
        $eu->associate_all_exact_text_matches($text);
    }


    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getFromEnvironment();
    }

    /** PRIVATE **/

    private function exec_sql($sql, $params = null) {
        // echo $sql . "\n";
        $stmt = $this->conn->prepare($sql);
        // dump("running $sql");
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


    private function associate_all_exact_text_matches(?Text $text) {
        $sql = "update textitems2
inner join words on ti2textlc = wotextlc and ti2lgid = wolgid
set ti2woid = woid
where ti2woid = 0";
        if ($text != null) {
            $sql .= " AND ti2TxID = {$text->getID()}";
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
    
        // For each expession in the language, add expressions for the sentence range.
        // Inefficient, but for now I don't care -- will see how slow it is.
        $sentenceRange = [ $firstSeID, $lastSeID ];
        $mwordsql = "SELECT * FROM words WHERE WoLgID = $lid AND WoWordCount > 1";
        $res = $this->conn->query($mwordsql);
        while ($record = mysqli_fetch_assoc($res)) {
            $this->insertExpressions(
                $record['WoTextLC'],
                $text->getLanguage(),
                $record['WoID'],
                $record['WoWordCount'],
                $sentenceRange);
        }
        mysqli_free_result($res);
    }


    private function associate_term_with_existing_texts(Term $term)
    {
        if ($term->getWordCount() == 1) {
            $woid = $term->getID();
            $lgid = $term->getLanguage()->getLgID();
            $updateti2sql = "UPDATE textitems2
              SET Ti2WoID = {$woid}
              WHERE Ti2WoID = 0 AND Ti2LgID = {$lgid} AND Ti2TextLC = ?";
            $params = array("s", $term->getTextLC());
            $this->exec_sql($updateti2sql, $params);
        }
        else {
            $this->insertExpressions(
                $term->getTextLC(),
                $term->getLanguage(),
                $term->getID(),
                $term->getWordCount()
            );
        }
    }

    private function break_all(Term $term)
    {
        $woid = $term->getID();
        $updateti2sql = "UPDATE textitems2
           SET Ti2WoID = 0 WHERE Ti2WoID = {$woid}";
        $this->exec_sql($updateti2sql);
    }

    /** Expressions **************************/


    // Note that sentence range feels redundant, but is used elsewhere when new expr defined and ll texts in language have to be updated.
    /**
     * @param string $textlc Text in lower case
     * @param Language the language
     * @param string $wordcount
     * @param array  $sentenceIDRange   [ lower SeID, upper SeID ] to consider.
     */
    private function insertExpressions(
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
        // dump("got sentences:");
        // dump($sentences);
        $this->insert_standard_expression(
            $sentences, $lang, $textlc, $wid, $wordcount
        );
    }
    
    
    // Ref https://stackoverflow.com/questions/1725227/preg-match-and-utf-8-in-php
    // Leaving the "echo" comments in, in case more future debugging needed.
    
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
        // echo "Returning:\n";
        // var_dump($result);
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
        // dump($sql);

        if ($removeSpaces == 1 && $splitEachChar == 0) {
            $sql = "SELECT 
                group_concat(Ti2Text ORDER BY Ti2Order SEPARATOR ' ') AS SeText, SeID, 
                SeTxID, SeFirstPos 
                FROM textitems2, sentences 
                WHERE {$whereSeIDRange} SeID=Ti2SeID AND SeLgID = $lid AND Ti2LgID = $lid 
                AND SeText LIKE LIKE concat('%', ?, '%') 
                AND Ti2WordCount < 2 
                GROUP BY SeID";
        }

        $notermchar = "/[^$termchar]({$textlc})[^$termchar]/ui";

        // TODO:japanese This is copied legacy code, but is not tested.
        // Note that the checks for splitEachChar and removeSpaces
        // alter the $string and $notermchar regex, but these changes
        // are not returned to the calling method ... so the calling
        // method won't be handling these things correctly.  Needs test cases.

        $params = [ 's', mysqli_real_escape_string($this->conn, $textlc) ];

        $countsql = "select count(*) as c from ($sql) src";
        $count = $this->exec_sql($countsql, $params);
        $record = mysqli_fetch_assoc($count);
        $c = $record['c'];
        mysqli_free_result($count);
        // dump("got $c sentences matching \"{$textlc}\"");

        $res = $this->exec_sql($sql, $params);
        $result = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $string = ' ' . $record['SeText'] . ' ';
            // dump("checking $string");
            if ($splitEachChar) {
                $string = preg_replace('/([^\s])/u', "$1 ", $string);
            } else if ($removeSpaces == 1) {
                $ma = $this->pregMatchCapture(
                    false,
                    '/(?<=[ ])(' . preg_replace('/(.)/ui', "$1[ ]*", $textlc) . 
                    ')(?=[ ])/ui', 
                    $string
                );
                if (!empty($ma[1])) {
                    $textlc = trim($ma[1]);
                    $notermchar = "/[^$termchar]({$textlc})[^$termchar]/ui";
                }
            }
            $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
            // dump("got $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');");
            if ($last_pos !== false)
                $result[] = $record;
        }
        mysqli_free_result($res);
        return $result;
    }


    /**
     * Insert an expression.
     * @param string $textlc Text to insert in lower case
     * @param string $lid    Language ID
     * @param int    $wid    Word ID of the expression
     * @param array  $sentenceIDRange
     */
    private function insert_standard_expression(
        $sentences, Language $lang, $textlc, $wid, $wordcount
    )
    {
        $lid = $lang->getLgID();
        $termchar = $lang->getLgRegexpWordCharacters();
        $notermchar = "/[^$termchar]({$textlc})[^$termchar]/ui";

        foreach ($sentences as $record) {
            $string = ' ' . $record['SeText'] . ' ';

            $rx = $notermchar;
            $allmatches = $this->pregMatchCapture(true, $notermchar, " $string ");
            $termmatches = [];
            if (count($allmatches) > 0)
                $termmatches = $allmatches[1];
            // Sample $termmatches data:
            // array(3) { [0]=> array(2) { [0]=> string(7) "Un gato", [1]=> int(2) }, ... }

            foreach($termmatches as $tm) {
                $cnt = $this->get_term_count_before($string, $tm[1], $termchar);
                $pos = 2 * $cnt + (int) $record['SeFirstPos'];
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


    private function get_term_count_before($string, $pos, $termchar): int {
        $beforesubstr = mb_substr($string, 0, $pos - 1, 'UTF-8');
        $termsbefore = $this->pregMatchCapture(true, "/([$termchar]+)/u", $beforesubstr);
        if (count($termsbefore) != 0)
            return count($termsbefore[1]);
        return 0;
    }

}