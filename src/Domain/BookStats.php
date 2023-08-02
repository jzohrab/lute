<?php

namespace App\Domain;

use App\Entity\Book;
use App\Repository\BookRepository;
use App\Domain\TokenCoverage;
use App\Entity\Language;
use App\Utils\Connection;

class BookStats {

    public static function refresh(BookRepository $book_repo) {
        $conn = Connection::getFromEnvironment();
        $books = BookStats::booksToUpdate($conn, $book_repo);
        if (count($books) == 0)
            return;

        $langids = array_map(
            fn($b) => $b->getLanguage()->getLgID(),
            $books);
        $langids = array_unique($langids);

        foreach ($langids as $langid) {
            $langbooks = array_filter(
                $books,
                fn($b) => $b->getLanguage()->getLgID() == $langid);
            foreach ($langbooks as $b) {
                $stats = BookStats::getStats($b, $conn);
                BookStats::updateStats($b, $stats, $conn);
            }
        }
    }

    public static function markStale(Book $book) {
        $conn = Connection::getFromEnvironment();
        $bkid = $book->getId();
        $sql = "delete from bookstats where BkID = $bkid";
        $conn->query($sql);
    }

    public static function recalcLanguage(Language $lang) {
        $conn = Connection::getFromEnvironment();
        $lgid = $lang->getLgId();
        $sql = "delete from bookstats
          where BkID in (select BkID from books where BkLgID = $lgid)";
        $conn->query($sql);
    }

    private static function booksToUpdate($conn, $book_repo): array {
        $sql = "select bkid from books
          where bkid not in (select bkid from bookstats)";
        $bkids = [];
        $res = $conn->query($sql);
        while ($row = $res->fetch(\PDO::FETCH_NUM)) {
            $bkids[] = intval($row[0]);
        }

        // This is not performant, but at the moment I don't care as
        // it's unlikely that there will be many book stats to update.
        $books = [];
        foreach ($bkids as $bkid) {
            $books[] = $book_repo->find($bkid);
        }
        return $books;
    }

    private static function getStats(
        Book $b,
        $conn
    )
    {
        $count = function($sql) use ($conn) {
            $res = $conn->query($sql);
            $row = $res->fetch(\PDO::FETCH_NUM);
            if ($row == false)
                return 0;
            return intval($row[0]);
        };

        $lgid = $b->getLanguage()->getLgID();
        $bkid = $b->getID();

        $tc = new TokenCoverage();
        $covstats = $tc->getStats($b);
        $unknowns = $covstats[0];
        $allunique = array_sum(array_values($covstats));

        $sql = "select count(toktextlc)
from texttokens
inner join texts on txid = toktxid
inner join books on bkid = txbkid
where tokisword = 1
and txbkid = {$bkid}
group by txbkid";
        $all = $count($sql);
        // dump($sql);
        // dump($all);

        $percent = 0;
        if ($allunique > 0) // In case not parsed.
            $percent = round(100.0 * $unknowns / $allunique);

        // Any change in the below fields requires a change to
        // updateStats as well, query insert doesn't check field
        // order..
        return [
            $all,
            $allunique,
            $unknowns,
            $percent
        ];
    }

    private static function updateStats($b, $stats, $conn) {
        if ($b->getID() == null)
            return;
        $vals = [
            $b->getId(),
            ...$stats
        ];
        $valstring = implode(',', $vals);
        $sql = "insert or ignore into bookstats
        (BkID, wordcount, distinctterms, distinctunknowns, unknownpercent)
        values ( $valstring )";
        $conn->query($sql);
    }
}
