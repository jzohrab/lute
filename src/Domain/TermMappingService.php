<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\Language;
use App\Entity\Book;
use App\Repository\LanguageRepository;
use App\Entity\Status;
use App\DTO\TermReferenceDTO;
use App\Utils\Connection;
use App\Repository\TermRepository;

/**
 * Bulk-map child terms to parent terms, creating things as needed.
 *
 * The mapping file contains parent - child term pairs, tab-delimited, eg:
 *
 *    gato	gatos
 *    blanco	blancos
 *
 */
class TermMappingService {

    private TermRepository $term_repo;
    private array $pendingTerms;

    public static function loadMappingFile($filename): array {
        $lines = explode("\n", file_get_contents($filename));
        // No blanks
        $lines = array_filter($lines, fn($lin) => trim($lin) != '');
        // No comments
        $lines = array_filter($lines, fn($lin) => $lin[0] != '#');
        $mappings = array_map(fn($s) => explode("\t", $s), $lines);
        // 2 elements
        $mappings = array_filter($mappings, fn($arr) => count($arr) == 2);
        // No blank parent/child
        $mappings = array_filter($mappings, fn($m) => ($m[0] ?? '') != '' && ($m[1] ?? '') != '');
        $mappings = array_map(fn($arr) => [ 'parent' => $arr[0], 'child' => $arr[1] ], array_values($mappings));
        return $mappings;
    }

    public function __construct(
        TermRepository $term_repo
    ) {
        $this->term_repo = $term_repo;
        $this->pendingTerms = array();
    }

    private function add(Term $term, bool $flush = true) {
        $this->pendingTerms[] = $term;
        $this->term_repo->save($term, false);
        if ($flush) {
            $this->flush();
        }
    }

    private function flush() {
        $this->term_repo->flush();
        $this->pendingTerms = array();
    }

    /** Kills everything in the entity manager. */
    private function flushClear() {
        $this->flush();
        $this->term_repo->clear();
        // $msg = "After clear, Memory usage: " . (memory_get_usage() / 1024);
        // dump($msg);
    }

    /**
     * Mappings.
     *
     * All mappings are loaded into a temp table,
     * zz_load_parent_mappings, with fields:
     * - parent TEXT
     * - child TEXT
     * - parentWoID integer null
     * - childWoID integer null
     *
     * The parent and child text fields are loaded from the supplied
     * mapping file, and the parentWoID and childWoID fields are
     * updated as new Term entities are created.  The WoID fields are
     * then loaded into the wordparents table.
     */

    /** Load temp table of distinct mappings. */
    private function loadTempTable($mappings, $conn) {
        $pre = "pre_zz_load_parent_mappings";
        $sql = "drop table if exists $pre";
        $conn->exec($sql);
        $sql = "CREATE TABLE $pre (parent TEXT, child TEXT)";
        $conn->exec($sql);

        $stmt = $conn->prepare("INSERT INTO $pre (parent, child) VALUES (?, ?)");
        foreach (array_chunk($mappings, 100) as $batch) {
            $conn->beginTransaction();
            $batchvals = array_map(fn($h) => [ $h['parent'], $h['child' ] ], $batch);
            foreach ($batchvals as $row) {
                $stmt->execute($row);
            }
            $conn->commit();
        }

        $sql = "insert into zz_load_parent_mappings (parent, child)
          select parent, child from $pre
          group by parent, child";
        $conn->exec($sql);

        $sql = "drop table if exists $pre";
        $conn->exec($sql);
    }

    /** Set the childWoID and parentWoID in zz_load_parent_mappings, matching
     * to existing word.WoIDs via straight text match.
     */
    private function setExistingIDs($lgid, $conn) {
        $queries = [
            "update zz_load_parent_mappings
            set childWoID = (select words.WoID from words
              where words.WoLgID = $lgid and words.WoTextLC = zz_load_parent_mappings.child)
            where childWoID is null",
            "update zz_load_parent_mappings
            set parentWoID = (select words.WoID from words
              where words.WoLgID = $lgid and words.WoTextLC = zz_load_parent_mappings.parent)
            where parentWoID is null"
        ];
        foreach ($queries as $sql) {
            // dump($sql);
            $conn->query($sql);
        }
    }

    /**
     * Map terms to parents, creating terms as needed.  This method
     * uses the model, which is probably much less efficient than
     * straight sql inserts, but I don't care at the moment.
     *
     * @param mappings   array of mappings, eg
     * [ [ 'parent' => 'gato', 'child' => 'gatos' ], [ 'parent' => 'blanco', 'child' => 'blancos' ], ... ]
     */
    public function mapParents(Language $lang, LanguageRepository $langrepo, $mappings) {

        $blank = function($s) { return trim($s ?? '') == ''; };
        $badmaps = array_filter($mappings, fn($a) => $blank($a['parent']) || $blank($a['child']));
        if (count($badmaps) > 0)
            throw new \Exception('Blank or null in mapping');

        $mappings = array_filter($mappings, fn($a) => $a['parent'] != $a['child']);
        $mappings = array_filter($mappings, fn($a) => $a['parent'] != null && $a['child'] != null);
        $mappings = array_map(fn($a) => [ 'parent' => mb_strtolower($a['parent']), 'child' => mb_strtolower($a['child']) ], $mappings);

        // dump($mappings);
        $this->term_repo->stopSqlLog();

        $conn = Connection::getFromEnvironment();
        $conn->exec("drop table if exists zz_load_parent_mappings");
        $sql = "CREATE TABLE zz_load_parent_mappings
          (parent TEXT, child TEXT, parentWoID integer null, childWoID integer null)";
        $conn->exec($sql);

        $this->loadTempTable($mappings, $conn);

        // Map existing ids.
        $lgid = $lang->getLgID();
        $this->setExistingIDs($lgid, $conn);

        $created = 0;
        $updated = 0;

        // PART I
        //
        // First, create any necessary parents, if there are existing
        // children present in the mapping file that refer to
        // non-existent parents.
        //
        // For example, if the mapping file contains "cat cats", and
        // the term "cats" (the child) exists, but the term "cat" (the
        // parent) does NOT, this section would create the new parent
        // term "cat".
        //
        // Note that if there are any new
        // _children_ that should be mapped to those parents, those
        // will be created later.  This isn't _great_ because it
        // implies net new term creation (both parent and child), but
        // it's not terrible.
        //
        $arr = [];
        $sql = "select parent, GROUP_CONCAT(child, '|') as children
          from zz_load_parent_mappings
          where parentWoID is null and childWoID is not null
          group by parent";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $arr[] = [ $row[0], $row[1] ];
        }
        $created += count($arr);
        foreach (array_chunk($arr, 100) as $batch) {
            // Stupid ... trying flushClear to prevent memory issues,
            // but that results in Doctrine "losing track" of $lang,
            // even though we still need it!
            $lang = $langrepo->find($lgid);
            foreach ($batch as $row) {
                $p = $row[0];
                $children = explode('|', $row[1]);
                $msg = 'Auto-created parent for "' . $children[0] . '"';
                $extra = count($children) - 1;
                if ($extra > 0)
                    $msg .= " + {$extra} more"; 
                // dump('adding new parent ' . $p);

                $t = new Term($lang, $p);
                $t->setFlashMessage($msg);
                $this->add($t, false);
                $t = null;
            }
            $this->flushClear();
        }
        $this->flushClear();
        // dump('END PART I');

        // Update the mapping table to account for any new parent IDs.
        $this->setExistingIDs($lgid, $conn);

        // PART II
        //
        // Now create any necessary children, if there are existing
        // parents present in the mapping file that refer to
        // non-existent children.
        //
        // For example, if the mapping file contains "cat cats", and
        // the term "cat" (the parent) exists, but the term "cats" (the
        // child) does NOT, this section would create the new child
        // term "cats".
        //
        // The parent-child relationship will be added in part III.
        $arr = [];
        $sql = "select child, parent, parentWoID
          from zz_load_parent_mappings
          where childWoID is null and parentWoID is not null
          group by child, parentWoID";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $c = $row[0];
            $p = $row[1];
            $pid = intval($row[2]);
            $arr[] = [ $c, $p, $pid ];
        }
        $created += count($arr);
        foreach (array_chunk($arr, 100) as $batch) {
            $lang = $langrepo->find($lgid);
            foreach ($batch as $row) {
                $c = $row[0];
                $p = $row[1];
                $pid = $row[2];
                $t = new Term($lang, $c);
                $t->setFlashMessage('Auto-created and mapped to parent "' . $p . '"');
                $this->add($t, false);
            }
            $this->flushClear();
        }
        $this->flushClear();
        // dump('END PART II');

        // Update the mapping table to account for any new child IDs.
        $this->setExistingIDs($lgid, $conn);

        // PART III
        //
        // Add any required parent-child relationships.
        // - don't replace existing relationships
        // - don't try to assign one child to multiple parents
        $sql = "select childWoID, parentWoID
          from zz_load_parent_mappings
          where childWoID is not null and parentWoID is not null

          and childWoID not in (select WpWoID from wordparents)

          and childWoID not in (
            select childWoID from zz_load_parent_mappings
            where childWoID is not null
            group by childWoID
            having count(*) > 1
          )

          group by childWoID, parentWoID";
        $countsql = "select count(*) from ({$sql}) src";
        $stmt = $conn->prepare($countsql);
        $stmt->execute();
        $updated += $stmt->fetchColumn();
        $insertsql = "insert into wordparents (WpWoID, WpParentWoID)
          select childWoID, parentWoID from ({$sql}) src";
        // dump($insertsql);
        $stmt = $conn->prepare($insertsql);
        $stmt->execute();
        // dump('END PART III');

        $conn->exec("DROP TABLE if exists zz_load_parent_mappings");

        return [
            'created' => $created,
            'updated' => $updated
        ];
    }


    /**
     * Export a file to be used for lemmatization process.
     * Include: new terms, terms without parents.
     * Return name of created file.
     */
    public function lemma_export_language(
        Language $language,
        string $outfile
    ): string {

        $lgid = $language->getLgID();

        $getArr = function($conn, $sql) {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        };

        $writeArr = function($arr, $handle) {
            foreach ($arr as $row) {
                $t = trim($row);
                fwrite($handle, $t . PHP_EOL);
            }
        };

        $conn = Connection::getFromEnvironment();
        $handle = fopen($outfile, 'w');

        // All existing terms that don't have parents.
        $sig = Status::IGNORED;
        $sql = "select WoTextLC
from words
left join wordparents on WpWoID = WoID
where WoLgID = $lgid
  and WpParentWoID is null
  and WoTokenCount = 1
  and WoStatus != {$sig}";
        $recs = $getArr($conn, $sql);
        $writeArr($recs, $handle);

        fclose($handle);

        return $outfile;
    }


    /**
     * Get all sentences.
     */
    private function getAllSentences(Book $book, $conn) {
        $bkid = $book->getId();
        $sql = "select SeText from
            sentences
            inner join texts on TxID = SeTxID
            where TxBkID = {$bkid}
            order by TxID";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $records = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        return $records;
    }


    /**
     * Export a file to be used for lemmatization process.
     * Include: new terms
     * Return name of created file.
     */
    public function lemma_export_book(
        Book $book,
        string $outfile
    ): string {

        $lgid = $book->getLanguage()->getLgID();
        $bkid = $book->getID();

        $getArr = function($conn, $sql) {
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        };

        $writeArr = function($arr, $handle) {
            foreach ($arr as $row) {
                $t = trim($row);
                fwrite($handle, $t . PHP_EOL);
            }
        };

        $conn = Connection::getFromEnvironment();
        $handle = fopen($outfile, 'w');

        // All new parsed word tokens.

        // Dev note: originally, I had written the query below to find
        // all "text tokens" that don't have a corresponding `words`
        // record.  The query runs correctly and quickly in the sqlite3
        // command line, but when run in PHP it was brutally slow
        // (i.e., 30+ seconds).  I'm not sure why, and can't be
        // bothered to try to figure it out.  Instead of using the
        // query, I'm just calculating the array difference (the
        // uncommented code), which runs fast and should not be _too_
        // brutal on memory.
        /*
        $sql = "select distinct(TokTextLC) from texttokens
          inner join texts on TxID = TokTxID
          inner join books on TxBkID = BkID
          left join (
            select WoTextLC from words where WoLgID = $lgid
          ) langwords on langwords.WoTextLC = TokTextLC
          where
            TokIsWord = 1 and BkLgID = $lgid and
            langwords.WoTextLC is null";
        */

        // UPDATE: re-parsing the book to get parsed tokens.
        //
        // For space/data redundancy, I'm moving away from storing all
        // of the parsed tokens in the db, so this routine just gets
        // all sentences.  Reparsing is done in chunks due to memory
        // constraints.
        //
        // This is slow, but (on my mac, at least) it doesn't time
        // out.  Not too concerned at the moment, as I'm not sure if
        // many users are using this feature.
        $sentences = $this->getAllSentences($book, $conn);
        $uniquewords = [];
        $arbitrary_chunk_size = 100;
        foreach (array_chunk($sentences, $arbitrary_chunk_size) as $sentbatch) {
            $zws = mb_chr(0x200B); // zero-width space.
            $txt = implode($zws, $sentbatch);
            $txt = str_replace($zws, '', $txt);
            $toks = $book->getLanguage()->getParsedTokens($txt);
            $words = array_filter($toks, fn($t) => $t->isWord);
            $words = array_map(fn($t) => mb_strtolower($t->token), $words);
            $uniquewords[] = array_unique($words);
        }
        $uniquewords = array_merge([], ...$uniquewords);
        $alltoks = array_unique($uniquewords);

        $sql = "select WoTextLC
from words
where WoLgID = $lgid and WoTokenCount = 1";
        $allwords = $getArr($conn, $sql);

        $newtoks = array_diff($alltoks, $allwords);
        $writeArr($newtoks, $handle);

        fclose($handle);

        return $outfile;
    }

}