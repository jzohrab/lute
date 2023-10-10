<?php

namespace App\Parse;

use App\Entity\Text;
use App\Entity\Language;
use App\Utils\Connection;


class ParsedTokenSaver {

    public function parse(Text $text) {
        $s = $text->getText();
        $lang = $text->getLanguage();
        $parser = $lang->getParser();
        $tokens = $parser->getParsedTokens($s, $lang);
        $sentences = $this->build_sentences($tokens);

        $conn = Connection::getFromEnvironment();
        $this->prepForImport($conn, $text->getID());
        $this->load_sentences($conn, $text->getID(), $sentences);
    }


    private function build_sentences($parsedtokens): array {
        $sentences = [];

        // Keep track of the current sentence and the token sort
        // order.
        $curr_sentence_tokens = [];
        $sentence_number = 1;

        foreach ($parsedtokens as $pt) {
            $curr_sentence_tokens[] = $pt;

            // Word ending with \r marks the end of the current
            // sentence.
            if ($pt->isEndOfSentence) {
                $ptstrings = array_map(fn($t) => $t->token, $curr_sentence_tokens);

                $zws = mb_chr(0x200B); // zero-width space.
                $s = implode($zws, $ptstrings);
                $s = trim($s, ' ');  // Remove spaces at start and end.

                // The zws is added at the start and end of each
                // sentence, to standardize the string search when
                // looking for terms.
                $s = $zws . $s . $zws;

                $sentences[] = [ $sentence_number, $s ];

                $curr_sentence_tokens = [];
                $sentence_number += 1;
            }
        }

        return $sentences;
    }

    private function prepForImport($conn, $txid) {
        $sql = "DELETE FROM sentences WHERE SeTxID = {$txid}";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }

    // Insert each sentence in a prepared statement,
    // where sentence record is [ sentence_num, sentence ].
    private function load_sentences($conn, $textid, $sentences) {
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
        $vals = array_map(fn($t) => '(' . implode(',', [ $textid, $t[0], '?' ]) . ')', $sentences);
        $valstring = implode(',', $vals);

        $sql = $sqlbase . $valstring;

        $stmt = $conn->prepare($sql);
        // https://www.php.net/manual/en/sqlite3stmt.bindvalue.php
        // Positional numbering starts at 1. !!!
        $prmIndex = 1;
        for ($i = 0; $i < count($sentences); $i++) {
            $w = $sentences[$i][1];
            $stmt->bindValue($prmIndex, $w, \PDO::PARAM_STR);
            $prmIndex += 1;
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
    }

}