<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\Language;
use App\DTO\TermReferenceDTO;
use App\Utils\Connection;
use App\Repository\TermRepository;

class Dictionary {

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
        mysqli_close($conn);
        return $ret;
    }

    private function buildTermReferenceDTOs($res) {
        $ret = [];
        while (($row = $res->fetch_assoc())) {
            $s = $row['SeText'];
            if ($s !== null)
                $s = trim($s);
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
          AND lower(SeText) like concat('%', 0xE2808B, ?, 0xE2808B, '%')
          LIMIT 20";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $s);
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        $res = $stmt->get_result();
        return $this->buildTermReferenceDTOs($res);
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
        $res = $stmt->get_result();
        $termstrings = [];
        while (($row = $res->fetch_assoc())) {
            $termstrings[] = $row['WoTextLC'];
        }
        
        $sql = "select distinct TxID, TxTitle, SeText
          from texts
          inner join sentences on SeTxID = TxID
          where lower(SeText) like concat('%', 0xE2808B, ?, 0xE2808B, '%') and TxArchived = 1
           ";
        $fullsql = array_fill(0, count($termstrings), $sql);
        $fullsql = implode(' UNION ', $fullsql);
        // dump($fullsql);

        $stmt = $conn->prepare($fullsql);
        if (!$stmt) {
            throw new \Exception($conn->error);
        }
        $stmt->bind_param(str_repeat('s', count($termstrings)), ...$termstrings);
        if (!$stmt->execute()) {
            throw new \Exception($stmt->error);
        }
        $res = $stmt->get_result();
        return $this->buildTermReferenceDTOs($res);
    }

}