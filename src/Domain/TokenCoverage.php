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

    private function getTextExtract(Book $book) {
        $conn = Connection::getFromEnvironment();
        $bkid = $book->getId();
        $sql = "select GROUP_CONCAT(TxText, char(10))
          from (
            select TxText from
            texts
            inner join books on BkID = TxBkID
            where BkID = {$bkid} and TxID >= ifnull(BkCurrentTxID, 0)
            order by TxID
            limit 50
          ) src";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $record = $stmt->fetch(\PDO::FETCH_NUM);
        return $record[0];
    }


    public function getStats(Book $book, TermService $term_service) {
        $ft = $this->getTextExtract($book);
        $pt = $book->getLanguage()->getParsedTokens($ft);
        $sgi = new SentenceGroupIterator($pt, 250);

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

            $tts = ParsedToken::createTextTokens($tokens);
            $renderable = RenderableCalculator::getRenderable($book->getLanguage(), $terms, $tts);
            $textitems = array_map(
                fn($i) => $i->makeTextItem(1, 1, 1, $book->getLanguage()),
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
