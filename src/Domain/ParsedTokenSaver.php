<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
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
        return $stmt;
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
        $setup = [
            "DELETE FROM sentences WHERE SeTxID in ($idjoin)",
            "DELETE FROM texttokens WHERE TokTxID in ($idjoin)",
            "DROP TABLE IF EXISTS `temptexttokens`",
            "CREATE TABLE `temptexttokens` (
              `TokTxID` INTEGER NOT NULL,
              `TokSentenceNumber` INTEGER NOT NULL,
              `TokOrder` INTEGER NOT NULL,
              `TokIsWord` tinyint NOT NULL,
              `TokText` varchar(100) NOT NULL
            )"
        ];
        foreach ($setup as $sql) {
            $this->exec_sql($sql);
        }

        $chunks = array_chunk($allinserts, 5000);
        foreach ($chunks as $chunk) {
            $this->load_temptexttokens($chunk);
        }

        $sqls = [
            "insert into texttokens (TokTxID, TokSentenceNumber, TokOrder, TokIsWord, TokText)
              select TokTxID, TokSentenceNumber, TokOrder, TokIsWord, TokText from temptexttokens",
            "DROP TABLE if exists `temptexttokens`",

            // Load sentences.
            // char(0x200B) (the zero-width space) is added between each
            // token, and at the start and end of each sentence, to
            // standardize the string search when looking for terms.
            "INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeFirstPos, SeText)
              SELECT TxLgID, TxID, TokSentenceNumber, min(TokOrder),
              char(0x200B) || TRIM(GROUP_CONCAT(TokText, char(0x200B))) || char(0x200B)
              FROM (
                select TxLgID, TxID, TokSentenceNumber, TokOrder, TokText
                from texttokens
                inner join texts on TxID = TokTxID
                WHERE TxID in ({$idjoin})
                order by TxLgID, TxID, TokSentenceNumber, TokOrder
              ) src
              group by TxLgID, TxID, TokSentenceNumber"
        ];
        foreach ($sqls as $sql) {
            $this->conn->query($sql);
        }

    }


    // Instance state required while loading temp table:
    private int $sentence_number = 0;
    private int $ord = 0;

    private function build_insert_array($txid, $tokens): array {
        // Make the array row, incrementing $sentence_number as
        // needed.
        $makeentry = function($token) use ($txid) {
            $isword = $token->isWord ? 1 : 0;
            $this->ord += 1;
            $ret = [ $txid, $this->sentence_number, $this->ord, $isword, $token->token ];

            // Word ending with \r marks the end of the current
            // sentence.
            if ($token->isEndOfSentence) {
                $this->sentence_number += 1;
            }
            return $ret;
        };

        $arr = array_map($makeentry, $tokens);

        // var_dump($arr);
        return $arr;
    }


    // Insert each record in chunk in a prepared statement,
    // where chunk record is [ txid, sentence_num, ord, wordcount, word ].
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
        //
        // "+ 1" on the sentence number is a relic of old code
        // ... sentences in the array were numbered starting at 0.
        // Can be amended in the future.
        $vals = array_map(fn($t) => '(' . implode(',', [ $t[0], $t[1] + 1, $t[2], $t[3], '?' ]) . ')', $chunk);
        $valstring = implode(',', $vals);

        $sql = $sqlbase . $valstring;

        $stmt = $this->conn->prepare($sql);
        // https://www.php.net/manual/en/sqlite3stmt.bindvalue.php
        // Positional numbering starts at 1. !!!
        $n = count($chunk);
        for ($i = 1; $i <= $n; $i++) {
            // TODO:sqlite uses SQLITE3_TEXT
            $stmt->bindValue($i, $chunk[$i - 1][4], \PDO::PARAM_STR);
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
    }

}