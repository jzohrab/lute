<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\Language;
use App\Repository\LanguageRepository;
use App\Entity\Status;
use App\DTO\TermReferenceDTO;
use App\Utils\Connection;
use App\Repository\TermRepository;

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
        return array_values($mappings);
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
        /* * /
        $msg = 'flushing ' . count($this->pendingTerms) . ' terms: ';
        foreach ($this->pendingTerms as $t) {
            $msg .= $t->getText();
            if ($t->getParent() != null)
                $msg .= " (parent " . $t->getParent()->getText() . ")";
            $msg .= ', ';
        }
        // dump($msg);
        /* */
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
     */

    /** Load temp table of mappings. */
    private function loadTempTable($tempTableName, $mappings, $conn) {
        $stmt = $conn->prepare("INSERT INTO $tempTableName (child, parent) VALUES (?, ?)");
        foreach (array_chunk($mappings, 100) as $batch) {
            $conn->beginTransaction();
            foreach ($batch as $row) {
                $stmt->execute($row);
            }
            $conn->commit();
        }
    }

    private function setExistingIDs($tempTableName, $lgid, $conn) {
        $queries = [
            "update $tempTableName
set childWoID = (select words.WoID from words where words.WoLgID = $lgid and words.WoTextLC = $tempTableName.child)
where childWoID is null",
            "update $tempTableName
set parentWoID = (select words.WoID from words where words.WoLgID = $lgid and words.WoTextLC = $tempTableName.parent)
where parentWoID is null"
        ];
        foreach ($queries as $sql)
            $conn->query($sql);
    }

    /**
     * Map terms to parents, creating terms as needed.  This method
     * uses the model, which is probably much less efficient than
     * straight sql inserts, but I don't care at the moment.
     *
     * @param mappings   array of mappings, eg [ [ 'gatos', 'gato' ], [ 'blancos', 'blanco' ], ... ]
     */
    public function mapParents(Language $lang, LanguageRepository $langrepo, $mappings) {

        $mappings = array_filter($mappings, fn($a) => $a[0][0] != '#');
        $mappings = array_filter($mappings, fn($a) => $a[0] != $a[1]);
        $nullorblank = function($s) { return trim($s ?? '') == ''; };
        $badmaps = array_filter($mappings, fn($a) => $nullorblank($a[0]) || $nullorblank($a[1]));
        if (count($badmaps) > 0)
            throw new \Exception('Blank or null in mapping');

        $this->term_repo->stopSqlLog();

        $conn = Connection::getFromEnvironment();
        $tempTableName = 'zz_load_mappings_' . uniqid();
        $sql = "CREATE TABLE $tempTableName
          (child TEXT, parent TEXT, childWoID integer null, parentWoID integer null)";
        $conn->exec($sql);

        $lgid = $lang->getLgID();
        $haveTemp = false;

        $this->loadTempTable($tempTableName, $mappings, $conn);
        $haveTemp = true;

        $this->setExistingIDs($tempTableName, $lgid, $conn);

        $created = 0;
        $updated = 0;

        // dump('PART I');
        // First, create any necessary parents, if there are existing
        // children that need parents.  Note that if there are any new
        // _children_ that should be mapped to those parents, those
        // will be created later.  This isn't _great_ because it
        // implies net new term creation (both parent and child), but
        // it's not terrible.

        // Missing parents with at least one existing child
        $arr = [];
        $sql = "select parent, GROUP_CONCAT(child, '|') as children
          from $tempTableName
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

        // Reset to account for new parents.
        $this->setExistingIDs($tempTableName, $lgid, $conn);

        // All necessary parents have been saved.  Now go through all
        // parents in the mapping file again, and map children,
        // creating new children if necessary.

        // dump('PART II');
        // Missing children with existing parent
        // The parent-child relationship will be added in the next step.
        $arr = [];
        $sql = "select child, parent, parentWoID
          from $tempTableName
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

        // Reset to account for new children.
        $this->setExistingIDs($tempTableName, $lgid, $conn);

        // dump('PART III');
        // Existing terms with no parent now, but have mapping to
        // existing parent
        $sql = "select childWoID, parentWoID
          from $tempTableName
          where childWoID is not null and parentWoID is not null
          and childWoID not in (select WpWoID from wordparents)
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

        if ($haveTemp) {
            $conn->exec("DROP TABLE $tempTableName");
        }

        return [
            'created' => $created,
            'updated' => $updated
        ];
    }

    /**
     * Export a file to be used for lemmatization process.
     * Include: new TextTokens, terms without parents.
     * Return name of created file.
     */
    public function lemma_export(
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

        // All new TextTokens.

        // Dev note: originally, I had written the query below to find
        // all `texttokens` that don't have a corresponding `words`
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

        $sql = "select distinct(TokTextLC)
from texttokens
inner join texts on TxID = TokTxID
inner join books on TxBkID = BkID
where
  TokIsWord = 1
  and BkLgID = $lgid";
        $alltoks = $getArr($conn, $sql);

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