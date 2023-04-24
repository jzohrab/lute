<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\Language;
use App\DTO\TermReferenceDTO;
use App\Utils\Connection;
use App\Repository\TermRepository;

class TermService {

    private TermRepository $term_repo;
    private array $pendingTerms;

    public function __construct(
        TermRepository $term_repo
    ) {
        $this->term_repo = $term_repo;
        $this->pendingTerms = array();
    }

    public function add(Term $term, bool $flush = true) {
        $this->pendingTerms[] = $term;
        $this->term_repo->save($term, false);
        if ($flush) {
            $this->flush();
        }
    }

    public function flush() {
        // dump('flushing ' . count($this->pendingTerms) . ' terms.');
        $this->term_repo->flush();
        $this->pendingTerms = array();
    }

    public function remove(Term $term): void
    {
        $this->term_repo->remove($term, false);
        $this->term_repo->flush();
    }

    /**
     * Find a term by an exact match.
     */
    public function find(string $value, Language $lang): ?Term {
        $spec = new Term($lang, $value);
        return $this->term_repo->findBySpecification($spec);
    }

    /**
     * Find Terms by matching text.
     */
    public function findMatches(string $value, Language $lang, int $maxResults = 50): array
    {
        $spec = new Term($lang, $value);
        return $this->term_repo->findLikeSpecification($spec, $maxResults);
    }

    /**
     * Find references.
     */
    public function findReferences(Term $term): array
    {
        $conn = Connection::getFromEnvironment();
        $p = $term->getParent();
        $ret = [
            'term' => $this->getReferences($term, $conn),
            'parent' => $this->getReferences($p, $conn),
            'children' => $this->getChildReferences($term, $conn),
            'siblings' => $this->getSiblingReferences($p, $term, $conn),
            'archived' => $this->getArchivedReferences($term, $conn)
        ];
        return $ret;
    }

    private function buildTermReferenceDTOs($termlc, $res) {
        $ret = [];
        $zws = mb_chr(0x200B); // zero-width space.
        while (($row = $res->fetch(\PDO::FETCH_ASSOC))) {
            $s = $row['SeText'];
            $s = trim($s);

            $pattern = "/{$zws}({$termlc}){$zws}/ui";
            $replacement = "{$zws}<b>" . '${1}' . "</b>{$zws}";
            $s = preg_replace($pattern, $replacement, $s);

            $ret[] = new TermReferenceDTO($row['TxID'], $row['TxTitle'], $s);
        }
        return $ret;
    }

    private function getReferences($term, $conn): array {
        if ($term == null)
            return [];
        $s = $term->getTextLC();
        $sql = "select distinct TxID, TxTitle, SeText
          from sentences
          inner join texts on TxID = SeTxID
          where TxArchived = 0
          AND lower(SeText) like '%' || char(0x200B) || ? || char(0x200B) || '%'
          LIMIT 20";
        $stmt = $conn->prepare($sql);

        // TODO:sqlite uses SQLITE3_TEXT
        $stmt->bindValue(1, $s, \PDO::PARAM_STR);

        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        return $this->buildTermReferenceDTOs($s, $stmt);
    }

    private function getSiblingReferences($parent, $term, $conn): array {
        if ($term == null || $parent == null)
            return [];
        $sibs = [];
        foreach ($parent->getChildren() as $s)
            $sibs[] = $s;
        $sibs = array_filter($sibs, fn($t) => $t->getID() != $term->getID());
        $ret = [];
        foreach ($sibs as $sib) {
            $ret[] = $this->getReferences($sib, $conn);
        }
        return array_merge([], ...$ret);
    }

    private function getChildReferences($term, $conn): array {
        if ($term == null)
            return [];
        $ret = [];
        foreach ($term->getChildren() as $c) {
            $ret[] = $this->getReferences($c, $conn);
        }
        return array_merge([], ...$ret);
    }

    private function getArchivedReferences($term, $conn): array {
        if ($term == null)
            return [];

        $wid = $term->getID();
        $wpid = -1;
        if ($term->getParent() != null) {
            $wpid = $term->getParent()->getID();
        }

        $sql = "select WoTextLC from words where WoID in
            (
              select WpWoID from wordparents
                where WpParentWoID in ({$wid}, {$wpid})
              union
              select {$wpid} as WoID
              union
              select {$wid} as WoID
            )";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new \Exception($conn->error);
        }
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        $termstrings = [];
        while (($row = $stmt->fetch(\PDO::FETCH_NUM))) {
            $termstrings[] = $row[0];
        }
        
        $sql = "select distinct TxID, TxTitle, SeText, SeOrder
          from texts
          inner join sentences on SeTxID = TxID
          where lower(SeText) like '%' || char(0x200B) || ? || char(0x200B) || '%' and TxArchived = 1
           ";
        $fullsql = array_fill(0, count($termstrings), $sql);
        $fullsql = implode(' UNION ', $fullsql);
        $fullsql = $fullsql . ' ORDER BY SeOrder';
        // dump($fullsql);

        $stmt = $conn->prepare($fullsql);
        if (!$stmt) {
            throw new \Exception($conn->error);
        }

        // https://www.php.net/manual/en/sqlite3stmt.bindvalue.php
        // Positional numbering starts at 1. !!!
        $n = count($termstrings);
        for ($i = 1; $i <= $n; $i++) {
        // TODO:sqlite uses SQLITE3_TEXT
            $stmt->bindValue($i, $termstrings[$i - 1], \PDO::PARAM_STR);
        }

        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        return $this->buildTermReferenceDTOs($term->getTextLC(), $stmt);
    }

}