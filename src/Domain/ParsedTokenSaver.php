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

    private function prepForImport($texts) {
        $allids = array_map(fn($t) => $t->getID(), $texts);
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
              `TokText` varchar(100) NOT NULL,
              `TokTextLC` varchar(100) NOT NULL
            )"
        ];
        foreach ($setup as $sql) {
            $this->exec_sql($sql);
        }
    }

    private function parseText($texts) {
        $this->conn = Connection::getFromEnvironment();

        $this->prepForImport($texts);

        $allids = [];
        $inserts = [];
        $colltokens = [];
        foreach ($texts as $text) {
            $allids[] = $text->getID();

            $s = $text->getText();
            // Replace double spaces, because they can mess up multi-word terms
            // (e.g., "llevar[ ][ ]a" is different from "llevar[ ]a").
            // Note that this code is duplicated in RenderableSentence.  Should extract.
            // TODO:remove_duplicate_logic
            $s = preg_replace('/ +/u', ' ', $s);
            $zws = mb_chr(0x200B); // zero-width space.
            $s = str_replace($zws, '', $s);

            $tokens = $this->parser->getParsedTokens($s, $text->getLanguage());
            $inserts[] = $this->build_sentence_insert_array($text->getID(), $tokens);
        }
        $allinserts = array_merge([], ...$inserts);

        $chunks = array_chunk($allinserts, 5000);
        foreach ($chunks as $chunk) {
            $this->load_sentences($chunk);
        }

        $idjoin = implode(',', $allids);
        $sqls = [
            "insert into texttokens (TokTxID, TokSentenceNumber, TokOrder, TokIsWord, TokText, TokTextLC)
              select TokTxID, TokSentenceNumber, TokOrder, TokIsWord, TokText, TokTextLC from temptexttokens",
            "DROP TABLE if exists `temptexttokens`",

            // Load sentences.
            // char(0x200B) (the zero-width space) is added between each
            // token, and at the start and end of each sentence, to
            // standardize the string search when looking for terms.
            "INSERT INTO sentences (SeTxID, SeOrder, SeText)
              SELECT TxID, TokSentenceNumber,
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


    private function build_sentence_insert_array($txid, $parsedtokens): array {
        $sentences = [];

        // Keep track of the current sentence and the token sort
        // order.
        $curr_sentence_tokens = [];
        $sentence_number = 0;

        foreach ($parsedtokens as $pt) {
            $curr_sentence_tokens[] = $pt;

            // Word ending with \r marks the end of the current
            // sentence.
            if ($pt->isEndOfSentence) {
                $ptstrings = array_map(fn($t) => $t->token, $curr_sentence_tokens);
                $zws = mb_chr(0x200B); // zero-width space.

                // The zws is added at the start and end of each
                // sentence, to standardize the string search when
                // looking for terms.
                $s = $zws . implode($zws, $ptstrings) . $zws;

                $sentences[] = [ $txid, $sentence_number, $s ];

                $curr_sentence_tokens = [];
                $sentence_number += 1;
            }
        }

        return $sentences;
    }


    // Insert each record in chunk in a prepared statement,
    // where chunk record is [ txid, sentence_num, sentence ].
    private function load_sentences(array $chunk) {
        $sqlbase = "insert into sentences (SeTxID, SeOrder, SeText) values ";

        // NOTE: I'm building the raw sql string for the integer
        // values, because it is _much_ faster to do this instead of
        // doing query params ("?") and later binding the params.
        // Originally, this used parameterized queries for the
        // inserts, but it took ~0.5 seconds to insert 500 records at
        // a time, and with straight values it takes < 0.01 second.
        // I'm not sure why, and can't be bothered to look into this
        // more.  (It could be due to how SQL logs queries ... but
        // that seems nuts.)
        $vals = array_map(fn($t) => '(' . implode(',', [ $t[0], $t[1], '?' ]) . ')', $chunk);
        $valstring = implode(',', $vals);

        $sql = $sqlbase . $valstring;

        $stmt = $this->conn->prepare($sql);
        // https://www.php.net/manual/en/sqlite3stmt.bindvalue.php
        // Positional numbering starts at 1. !!!
        $prmIndex = 1;
        for ($i = 0; $i < count($chunk); $i++) {
            $w = $chunk[$i][2];
            $wlc = mb_strtolower($w);
            $stmt->bindValue($prmIndex, $w, \PDO::PARAM_STR);
            $prmIndex += 1;
            // $stmt->bindValue($prmIndex + 1, $wlc, \PDO::PARAM_STR);
            // $prmIndex += 2;
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
    }

}