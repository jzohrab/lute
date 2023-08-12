<?php

namespace App\Domain;

use App\Entity\Book;
use App\Entity\Language;
use App\Entity\Term;
use App\Domain\TermService;
use App\Repository\TermRepository;
use App\Utils\Connection;
use App\DTO\TextToken;

/** Helper class for finding coverage of tokens for a given text string. */
class TokenCoverage {

    private function getFullText(Book $book) {
        $conn = Connection::getFromEnvironment();
        $bkid = $book->getId();
        $sql = "select GROUP_CONCAT(TxText, char(10))
          from (
            select TxText from
            texts
            inner join books on BkID = TxBkID
            where BkID = {$bkid} and TxID >= ifnull(BkCurrentTxID, 0)
            order by TxID
          ) src";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $record = $stmt->fetch(\PDO::FETCH_NUM);
        return $record[0];
    }

    private function getParsedTokens($book) {
        $ft = $this->getFullText($book);
        return $book->getLanguage()->getParsedTokens($ft);
    }

    private function createTextTokens($parsedTokens) {
        $ret = [];
        $i = 0;
        foreach ($parsedTokens as $pt) {
            $tok = new TextToken();
            $tok->TokSentenceNumber = 1;
            $tok->TokParagraphNumber = 1;
            $tok->TokOrder = $i;
            $i += 1;
            $tok->TokIsWord = $pt->isWord;
            $tok->TokText = $pt->token;
            $tok->TokTextLC = mb_strtolower($pt->token);
            $ret[] = $tok;
        }
        return $ret;

    }


    // Returns partially hydrated Terms (TextLC and TokenCount only).
    private function findTermsInParsedTokens($tokens, Language $lang) {

        // 1. Build query to get terms.  Building full query instead
        // of using named params, only using query once so no benefit
        // to parameterizing.
        $conn = Connection::getFromEnvironment();

        $wordtokens = array_filter($tokens, fn($t) => $t->isWord);
        $tokstrings = array_map(fn($t) => mb_strtolower($t->token), $wordtokens);
        $tokstrings = array_unique($tokstrings);
        $termcriteria = array_map(fn($s) => $conn->quote($s), $tokstrings);
        $termcriteria = implode(',', $termcriteria);

        $zws = mb_chr(0x200B); // zero-width space.
        $is = array_map(fn($t) => $t->token, $tokens);
        $lctokstring = mb_strtolower($zws . implode($zws, $is) . $zws);
        $lctokstring = $conn->quote($lctokstring);

        // Querying all words that match the text is very slow, so
        // breaking it up into two parts.
        $lgid = $lang->getLgID();
        $sql = "select WoTextLC, 1 as WoTokenCount, WoStatus from words
            where wotextlc in ($termcriteria)
            and WoTokenCount = 1 and WoLgID = $lgid

            UNION

            select WoTextLC, WoTokenCount, WoStatus from words
            where WoLgID = $lgid AND
            WoTokenCount > 1 AND
            instr($lctokstring, WoTextLC) > 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $terms = [];
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $t = new Term();
            $t->setTextLC($row[0])
                ->setTokenCount(intval($row[1]))
                ->setStatus(intval($row[2]));
            $terms[] = $t;
        }

        return $terms;
    }

    public function getStats(Book $book, TermService $term_service) {
        $pt = $this->getParsedTokens($book);
        $sgi = new SentenceGroupIterator($pt, 500);

        $maxcount = $sgi->count();
        if ($maxcount > 20)
            $maxcount = 20;

        $statterms = [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => [],
            98 => [],
            99 => []
        ];

        $counter = 0;
        for ($c = 0; $c < $maxcount; $c++) {
            $tokens = $sgi->next();

            // Sample timing code used to find slow spots:
            // $time_now = microtime(true);
            // /* after some operation */ $trace[] = 'note: ' . (microtime(true) - $time_now);

            $tokentext = array_map(fn($t) => $t->token, $tokens);
            $s = implode('', $tokentext);
            $terms = $term_service->findAllInString($s, $book->getLanguage());

            $tts = $this->createTextTokens($tokens);
            $renderable = RenderableCalculator::getRenderable($terms, $tts);
            $textitems = array_map(
                fn($i) => $i->makeTextItem(1, 1, 1, $book->getLanguage()->getLgID()),
                $renderable
            );

            $words = array_filter($textitems, fn($ti) => $ti->IsWord != 0);
            foreach (array_keys($statterms) as $statusval) {
                $words_with_status = array_filter($words, fn($w) => ($w->WoStatus ?? 0) == $statusval);
                $statterms[$statusval][] = array_map(fn($w) => $w->TextLC, $words_with_status);
            }
        }

        $stats = [];
        foreach (array_keys($statterms) as $statusval) {
            $uniques = array_unique(array_merge([], ...$statterms[$statusval]));
            $statterms[$statusval] = $uniques;
            $stats[$statusval] = count($uniques);
        }

        return $stats;
    }

}
