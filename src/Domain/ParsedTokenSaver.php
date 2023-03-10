<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Repository\TextItemRepository;
use App\Domain\TextStatsCache;
use App\Utils\Connection;

// TODO:hacking rename this
class ParsedTokenSaver {

    /** PUBLIC **/
    
    private $conn;
    private $parser = null;

    public function __construct(AbstractParser $p) {
        $this->parser = $p;
    }

    public function parse(Text $text) {
        $this->parseText($text);
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
        $this->conn = Connection::getFromEnvironment();

        // dump("parsing text ===============");
        $start = microtime(true);
        $curr = microtime(true);
        $traces = [];

        $trace = function($msg) use (&$curr, $start, &$traces) {
            $elapsed = (microtime(true) - $curr);
            $sincestart = (microtime(true) - $start);
            $t = "{$msg}: {$elapsed}; since start: {$sincestart}";
            // dump($t);
            $traces[] = [ $msg, $elapsed, $sincestart ];
            $curr = microtime(true);
        };

        $id = $text->getID();
        $cleanup = [
            "DROP TABLE IF EXISTS temptextitems",
            "DELETE FROM sentences WHERE SeTxID = $id",
            "DELETE FROM textitems2 WHERE Ti2TxID = $id",
            "DELETE FROM texttokens WHERE TokTxID = $id"
        ];
        foreach ($cleanup as $sql) {
            $this->exec_sql($sql);
            $trace($sql);
        }
        // $trace("drops");

        $s = $text->getText();
        $zws = mb_chr(0x200B); // zero-width space.
        $s = str_replace($zws, '', $s);
        $trace("replaces");
        $tokens = $this->parser->getParsedTokens($text->getText(), $text->getLanguage());
        $trace("got tokens");

        $arr = $this->build_insert_array($tokens);
        $trace("built array");

        // To be enabled later.
        $chunks = array_chunk($arr, 1000);
        foreach ($chunks as $chunk) {
            $this->load_texttokens($id, $chunk);
        }

        $this->load_temptextitems_from_array($arr);
        $trace("loaded temp table");
        $this->import_temptextitems($text);
        $trace("imported temp table");

        TextItemRepository::mapForText($text);
        $trace("mapped text items");

        TextStatsCache::force_refresh($text);
        $trace("loaded cache");

        // dump($traces);
        $this->exec_sql("DROP TABLE IF EXISTS temptextitems");
    }


    // Instance state required while loading temp table:
    private int $sentence_number = 0;
    private int $ord = 0;

    private function build_insert_array($tokens): array {
        // Make the array row, incrementing $sentence_number as
        // needed.
        $makeentry = function($token) {
            $isword = $token->isWord ? 1 : 0;
            $s = $token->token;
            $this->ord += 1;
            $ret = [ $this->sentence_number, $this->ord, $isword, rtrim($s, "\r") ];

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

    // Insert each record in chunk in a prepared statement,
    // where chunk record is [ sentence_num, ord, wordcount, word ].
    private function load_texttokens(int $txid, array $chunk) {
        $sqlbase = "insert into texttokens (TokTxID, TokSentenceNumber, TokOrder, TokIsWord, TokText) values ";
        $n = count($chunk);
        // "+ 1" on the sentence number is a relic of old code
        // ... sentences in the array were numbered starting at 0.
        // Can be amended in the future.
        $valplaceholders = str_repeat("({$txid},? + 1,?,?,?),", $n);
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