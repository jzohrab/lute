<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Repository\TextItemRepository;
use App\Domain\TextStatsCache;
use App\Utils\Connection;

class RomanceLanguageParser {

    /** PUBLIC **/
    
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::getFromEnvironment();
    }

    public function parse(Text $text) {
        $this->parseText($text);
    }

    public function getParsedTokens(string $text, Language $lang) {
        return $this->parse_to_tokens($text, $lang);
    }

    /** PRIVATE **/

    private function exec_sql($sql, $params = null) {
        // echo $sql . "\n";
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
 
    private function parseText(Text $text) {

        $id = $text->getID();
        $cleanup = [
            "DROP TABLE IF EXISTS temptextitems",
            "DELETE FROM sentences WHERE SeTxID = $id",
            "DELETE FROM textitems2 WHERE Ti2TxID = $id"
        ];
        foreach ($cleanup as $sql)
            $this->exec_sql($sql);

        $tokens = $this->parse_to_tokens($text->getText(), $text->getLanguage());
        $arr = $this->build_insert_array($tokens);
        $this->load_temptextitems_from_array($arr);
        $this->import_temptextitems($text);

        TextItemRepository::mapForText($text);

        TextStatsCache::force_refresh($text);

        // $this->exec_sql("DROP TABLE IF EXISTS temptextitems");
    }


    // A (possibly) easier way to do substitutions -- each
    // pair in $replacements is run in order.
    // Possible entries:
    // ( <src string or regex string (starting with '/')>, <target (string or callback)> )
    private function do_replacements($text, $replacements) {
        foreach($replacements as $r) {
            if ($r == 'skip') {
                continue;
            }

            if ($r == 'trim') {
                $text = trim($text);
                continue;
            }

            $src = $r[0];
            $tgt = $r[1];

            // echo "=====================\n";
            // echo "Applying '$src' \n\n";

            if (! is_string($tgt)) {
                $text = preg_replace_callback($src, $tgt, $text);
            }
            else {
                if (substr($src, 0, 1) == '/')
                    $text = preg_replace($src, $tgt, $text);
                else
                    $text = str_replace($src, $tgt, $text);
            }

            // echo "text is ---------------------\n";
            // echo str_replace("\r", "<RET>\n", $text);
            // echo "\n-----------------------------\n";
        }
        return $text;
    }

    private function parse_to_tokens(string $text, Language $lang) {

        $replace = explode("|", $lang->getLgCharacterSubstitutions());
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $rfrom = trim($fromto[0]);
                $rto = trim($fromto[1]);
                $text = str_replace($rfrom, $rto, $text);
            }
        }

        $punct = ParserPunctuation::PUNCTUATION . '\]';

        $splitSentence = $lang->getLgRegexpSplitSentences();
        $termchar = $lang->getLgRegexpWordCharacters();

        $resplitsent = "/(\S+)\s*((\.+)|([$splitSentence]))([]$punct]*)(?=(\s*)(\S+|$))/u";
        $splitSentencecallback = function($matches) use ($lang) {
            $splitex = $lang->getLgExceptionsSplitSentences();
            return $this->find_latin_sentence_end($matches, $splitex);
        };

        $splitThenPunct = "[{$splitSentence}][{$punct}]*";

        /**
         * Following is a hairy set of regular expressions and their
         * replacements that are applied in order.  These were copied
         * practically verbatim from LWT's parsing, and so they have
         * been production-tested by users with their texts.  Some of
         * these regex's may be redundant, or the order in some cases
         * might not matter -- hard to say offhand without getting a
         * bunch of test cases to verify behaviour before refactoring
         * the regexes.
         */
        $text = $this->do_replacements($text, [
            [ "\r\n",  "\n" ],
            [ '{',     '['],
            [ '}',     ']'],
            [ "\n",    ' ¶' ],

            $lang->isLgSplitEachChar() ?
            [ '/([^\s])/u', "$1\t" ] : 'skip',

            'trim',
            [ '/\s+/u',                             ' ' ],
            [ $resplitsent,                         $splitSentencecallback ],
            [ "¶",                                  "¶\r" ],
            [ " ¶",                                 "\r¶" ],
            [ '/([^' . $termchar . '])/u',          "\n$1\n" ],
            [ '/\n(' . $splitThenPunct . ')\n\t/u', "\n$1\n" ],
            [ '/([0-9])[\n]([:.,])[\n]([0-9])/u',   "$1$2$3" ],
            [ "\t",                                 "\n" ],
            [ "\n\n",                               "" ],
            [ "/\r(?=[{$punct} ]*\r)/u",            "" ],
            [ '/[\n]+\r/u',                         "\r" ],
            [ '/\r([^\n])/u',                       "\r\n$1" ],
            [ "/\n[.](?![{$punct}]*\r)/u",          ".\n" ],
            [ "/(\n|^)(?=.?[$termchar][^\n]*\n)/u", "\n1\t" ],
            'trim',
            [ "/(\n|^)(?!1\t)/u",                   "\n0\t" ],

            'trim'
        ]);

        $tokens = [];
        foreach(explode("\n", $text) as $line) {
            if (trim($line) != '') {
                [ $wordcount, $s ] = explode("\t", $line);
                $tokens[] = new ParsedToken($s, $wordcount > 0);
            }
        } 
        return $tokens;
    }


    // Instance state required while loading temp table:
    private int $sentence_number = 0;
    private int $ord = 0;

    private function build_insert_array($tokens): array {
        // Make the array row, incrementing $sentence_number as
        // needed.
        $makeentry = function($token) {
            $wordcount = $token->isWord ? 1 : 0;
            $s = $token->token;
            $this->ord += 1;
            $ret = [ $this->sentence_number, $this->ord, intval($wordcount), rtrim($s, "\r") ];

            // Word ending with \r marks the end of the current
            // sentence.
            if (str_ends_with($s, "\r")) {
                $this->sentence_number += 1;
            }
            return $ret;
        };

        $arr = array_map($makeentry, $tokens);

        // var_dump($arr);
        return $arr;
    }


    // Load array
    private function load_temptextitems_from_array(array $arr) {
        $this->conn->query("drop table if exists temptextitems");

        // Note the charset/collation here is very important!
        // If not used, then when the import is done, a new text item
        // can match to both an accented *and* unaccented word.
        $sql = "CREATE TABLE temptextitems (
          TiSeID mediumint(8) unsigned NOT NULL,
          TiOrder smallint(5) unsigned NOT NULL,
          TiWordCount tinyint(3) unsigned NOT NULL,
          TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
        ) ENGINE=MEMORY DEFAULT CHARSET=utf8";
        $this->conn->query($sql);

        $chunks = array_chunk($arr, 1000);

        foreach ($chunks as $chunk) {
            $this->insert_array_chunk($chunk);
        }
    }

    // Insert each record in chunk in a prepared statement,
    // where chunk record is [ sentence_num, ord, wordcount, word ].
    private function insert_array_chunk(array $chunk) {
        $sqlbase = "insert into temptextitems values ";
        $n = count($chunk);
        $valplaceholders = str_repeat("(?,?,?,?),", $n);
        $valplaceholders = rtrim($valplaceholders, ',');
        $parmtypes = str_repeat("iiis", $n);

        // Flatten the records in the chunks.
        // Ref belyas's solution in https://gist.github.com/SeanCannon/6585889.
        $prmarray = [];
        array_map(
            function($arr) use (&$prmarray) {
                $prmarray = array_merge($prmarray, $arr);
            },
            $chunk
        );

        $sql = $sqlbase . $valplaceholders;
        // echo $sql . "\n";
        // echo $parmtypes . "\n";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($parmtypes, ...$prmarray);
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
    }


    // TODO:refactor - this code is tough to follow. :-)
    /**
     * Find end-of-sentence characters in a sentence using latin alphabet.
     * 
     * @param string[] $matches       All the matches from a capturing regex
     * @param string   $noSentenceEnd If different from '', can declare that a string a not the end of a sentence.
     * 
     * @return string $matches[0] with ends of sentences marked with \t and \r.
     */
    private function find_latin_sentence_end($matches, $noSentenceEnd)
    {
        if (!strlen($matches[6]) && strlen($matches[7]) && preg_match('/[a-zA-Z0-9]/', substr($matches[1], -1))) { 
            return preg_replace("/[.]/", ".\t", $matches[0]); 
        }
        if (is_numeric($matches[1])) {
            if (strlen($matches[1]) < 3) { 
                return $matches[0];
            }
        } else if ($matches[3] && (preg_match('/^[B-DF-HJ-NP-TV-XZb-df-hj-np-tv-xz][b-df-hj-np-tv-xzñ]*$/u', $matches[1]) || preg_match('/^[AEIOUY]$/', $matches[1]))
        ) { 
            return $matches[0]; 
        }
        if (preg_match('/[.:]/', $matches[2]) && preg_match('/^[a-z]/', $matches[7])) {
            return $matches[0];
        }
        if ($noSentenceEnd != '' && preg_match("/^($noSentenceEnd)$/", $matches[0])) {
            return $matches[0]; 
        }
        return $matches[0] . "\r";
    }


    /**
     * Move data from temptextitems to final tables.
     * 
     * @param int    $id  New default text ID
     * @param int    $lid New default language ID
     * 
     * @return void
     */
    private function import_temptextitems(Text $text)
    {
        $id = $text->getID();
        $lid = $text->getLanguage()->getLgID();

        // 0xE2808B (the zero-width space) is added between each
        // token, and at the start and end of each sentence, to
        // standardize the string search when looking for terms.
        $sql = "INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeFirstPos, SeText)
            SELECT {$lid}, {$id}, TiSeID, 
            min(TiOrder),
            CONCAT(0xE2808B, GROUP_CONCAT(TiText order by TiOrder SEPARATOR 0xE2808B), 0xE2808B)
            FROM temptextitems 
            group by TiSeID";
        $this->exec_sql($sql);

        $minmax = "SELECT MIN(SeID) as minseid, MAX(SeID) as maxseid FROM sentences WHERE SeTxID = {$id}";
        $rec = $this->conn
             ->query($minmax)->fetch_array();
        $firstSeID = intval($rec['minseid']);
        $lastSeID = intval($rec['maxseid']);
    
        $addti2 = "INSERT INTO textitems2 (
                Ti2LgID, Ti2TxID, Ti2WoID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text, Ti2TextLC
            )
            select {$lid}, {$id}, WoID, TiSeID + {$firstSeID}, TiOrder, TiWordCount, TiText, lower(TiText) 
            FROM temptextitems 
            left join words 
            on lower(TiText) = WoTextLC and TiWordCount>0 and WoLgID = {$lid} 
            order by TiOrder,TiWordCount";

        $this->exec_sql($addti2);
    }

}