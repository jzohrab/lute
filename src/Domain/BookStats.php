<?php

namespace App\Domain;

use App\Entity\Book;
use App\Utils\Connection;

class BookStats {

    public static function refresh($book_repo) {
        $conn = Connection::getFromEnvironment();
        $books = BookStats::booksToUpdate($conn, $book_repo);
        if (count($books) == 0)
            return;

        $langids = array_map(
            fn($b) => $b->getLanguage()->getLgID(),
            $books);
        $langids = array_unique($langids);

        foreach ($langids as $langid) {
            $allwords = BookStats::getAllWords($langid, $conn);
            $langbooks = array_filter(
                $books,
                fn($b) => $b->getLanguage()->getLgID() == $langid);
            foreach ($langbooks as $b) {
                $stats = BookStats::getStats($b, $allwords);
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
    
    private static function booksToUpdate($conn, $book_repo): array {
        $sql = "select bkid from books
          where bkid not in (select bkid from bookstats)";
        $bkids = [];
        $res = $conn->query($sql);
        while (($row = $res->fetch_assoc())) {
            $bkids[] = intval($row['bkid']);
        }

        // This is not performant, but at the moment I don't care as
        // it's unlikely that there will be many book stats to update.
        $books = [];
        foreach ($bkids as $bkid) {
            $books[] = $book_repo->find($bkid);
        }
        return $books;
    }


    private static function getAllWords($langid, $conn) {
        $sql = "select WoTextLC from words
          where WoTokenCount = 1 and WoLgID = {$langid}";
        $allwords = [];
        $res = $conn->query($sql);
        while (($row = $res->fetch_assoc())) {
            $allwords[] = $row['WoTextLC'];
        }
        return $allwords;
    }
    
    private static function getStats(
        Book $b,
        array $allwords
    )
    {
        $fulltext = [];
        foreach ($b->getTexts() as $t)
            $fulltext[] = $t->getText();
        $fulltext = implode("\n", $fulltext);
        $lang = $b->getLanguage();
        $p = $lang->getParser();
        $texttokens = $p->getParsedTokens($fulltext, $lang);
        $textwords = array_filter($texttokens, fn($t) => $t->isWord);

        $textwordstrings = array_map(fn($t) => str_replace("\r", '', mb_strtolower($t->token)), $textwords);
        $uniquewordstrings = array_unique($textwordstrings);
        $unknowns = array_unique(array_values(array_diff($uniquewordstrings, $allwords)));
        $percent = round(100.0 * count($unknowns) / count($uniquewordstrings));

        // Any change in the below fields requires a change to
        // updateStats as well, query insert doesn't check field
        // order..
        return [
            count($textwords),
            count($uniquewordstrings),
            count($unknowns),
            $percent
        ];
    }

    private static function updateStats($b, $stats, $conn) {
        $vals = [
            $b->getId(),
            ...$stats
        ];
        $valstring = implode(',', $vals);
        $sql = "insert ignore into bookstats
        (BkID, wordcount, distinctterms, distinctunknowns, unknownpercent)
        values ( $valstring )";
        $conn->query($sql);
    }
}
