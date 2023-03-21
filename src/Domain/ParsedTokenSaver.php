<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Repository\TextItemRepository;
use App\Utils\Connection;


class ParsedTokenSaver {

    /** PUBLIC **/
    
    private $conn;
    private $parser = null;

    public function __construct(AbstractParser $p) {
        $this->parser = $p;
    }

    public function parse($texts) {
        $this->parseText($texts);
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
 
    private function parseText($texts) {
        $this->conn = Connection::getFromEnvironment();

        $allids = [];
        $inserts = [];
        $colltokens = [];
        foreach ($texts as $text) {
            // Reset token/sentence counters for text.
            // Global state sucks.
            $this->sentence_number = 0;
            $this->ord = 0;

            $s = $text->getText();
            $zws = mb_chr(0x200B); // zero-width space.
            $s = str_replace($zws, '', $s);
            $tokens = $this->parser->getParsedTokens($s, $text->getLanguage());

            $id = $text->getID();
            $allids[] = $id;
            $inserts[] = $this->build_insert_array($id, $tokens);
        }
        $allinserts = array_merge([], ...$inserts);

        $idjoin = implode(',', $allids);
        $cleanup = [
            "DROP TABLE IF EXISTS temptextitems",
            "DELETE FROM sentences WHERE SeTxID in ($idjoin)",
            "DELETE FROM textitems2 WHERE Ti2TxID in ($idjoin)",
            "DELETE FROM texttokens WHERE TokTxID in ($idjoin)"
        ];
        foreach ($cleanup as $sql) {
            $this->exec_sql($sql);
        }

        $this->load_texttokens($allinserts);
        $this->load_sentences($allids);
    }


    // Instance state required while loading temp table:
    private int $sentence_number = 0;
    private int $ord = 0;

    private function build_insert_array($txid, $tokens): array {
        // Make the array row, incrementing $sentence_number as
        // needed.
        $makeentry = function($token) use ($txid) {
            $isword = $token->isWord ? 1 : 0;
            $s = $token->token;
            $this->ord += 1;
            $ret = [ $txid, $this->sentence_number, $this->ord, $isword, rtrim($s, "\r") ];

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

    private function load_texttokens($allinserts) {
        $sql = "SET GLOBAL max_heap_table_size = 1024 * 1024 * 1024 * 2";
        $this->conn->query($sql);
        $sql = "SET GLOBAL tmp_table_size = 1024 * 1024 * 1024 * 2";
        $this->conn->query($sql);

        $sql = "DROP TABLE IF EXISTS `temptexttokens`";
        $this->exec_sql($sql);
        $sql = "CREATE TABLE `temptexttokens` (
          `TokTxID` smallint unsigned NOT NULL,
          `TokSentenceNumber` mediumint unsigned NOT NULL,
          `TokOrder` smallint unsigned NOT NULL,
          `TokIsWord` tinyint NOT NULL,
          `TokText` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL
        ) ENGINE=Memory DEFAULT CHARSET=utf8mb3";
        $this->exec_sql($sql);

        $sql = "SET GLOBAL general_log = 'OFF';";
        $this->conn->query($sql);

        $chunks = array_chunk($allinserts, 5000);
        foreach ($chunks as $chunk) {
            $this->load_temptexttokens($chunk);
        }

        $sql = "SET GLOBAL general_log = 'ON';";
        $this->conn->query($sql);

        $sql = "insert into texttokens (TokTxID, TokSentenceNumber, TokOrder, TokIsWord, TokText)
          select TokTxID, TokSentenceNumber, TokOrder, TokIsWord, TokText from temptexttokens";
        $this->exec_sql($sql);

        $sql = "DROP TABLE if exists `temptexttokens`";
        $this->exec_sql($sql);
    }

    // Insert each record in chunk in a prepared statement,
    // where chunk record is [ sentence_num, ord, wordcount, word ].
    private function load_temptexttokens(array $chunk) {
        $sqlbase = "insert into temptexttokens (TokTxID, TokSentenceNumber, TokOrder, TokIsWord, TokText) values ";

        // NOTE: I'm building the raw sql string for the integer
        // values, because it is _much_ faster to do this instead of
        // doing query params ("?") and later binding the params.
        // Originally, this used parameterized queries for the
        // inserts, but it took ~0.5 seconds to insert 500 records at
        // a time, and with straight values it takes < 0.01 second.
        // I'm not sure why, and can't be bothered to look into this
        // more.  (It could be due to how SQL logs queries ... but
        // that seems nuts.)
        
        // "+ 1" on the sentence number is a relic of old code
        // ... sentences in the array were numbered starting at 0.
        // Can be amended in the future.
        $vals = array_map(fn($t) => '(' . implode(',', [ $t[0], $t[1] + 1, $t[2], $t[3], '?' ]) . ')', $chunk);
        $valstring = implode(',', $vals);

        $n = count($chunk);
        $parmtypes = str_repeat("s", $n);
        $prmarray = array_map(fn($t) => $t[4], $chunk);

        $sql = $sqlbase . $valstring;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($parmtypes, ...$prmarray);
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
    }

    private function load_sentences($allids) {
        $idlist = implode(',', $allids);

        // 0xE2808B (the zero-width space) is added between each
        // token, and at the start and end of each sentence, to
        // standardize the string search when looking for terms.
        $sql = "INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeFirstPos, SeText)
            SELECT TxLgID, TxID, TokSentenceNumber, min(TokOrder),
            CONCAT(0xE2808B, GROUP_CONCAT(TokText order by TokOrder SEPARATOR 0xE2808B), 0xE2808B)
            FROM texttokens
            inner join texts on TxID = TokTxID
            WHERE TxID in ({$idlist})
            group by TxLgID, TxID, TokSentenceNumber";
        $this->exec_sql($sql);
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