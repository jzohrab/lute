<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TermRepository;


class Dictionary {

    private EntityManagerInterface $manager;
    private TermRepository $termrepo;

    public function __construct(
        EntityManagerInterface $manager,
        TermRepository $term_repo
    ) {
        $this->manager = $manager;
        $this->term_repo = $term_repo;
    }

    public function findTerm(string $value, Language $lang) {
        // Using Doctrine Query Language --
        // Interesting, but am not totally confident with it.
        // e.g. That I had to use the private field WoTextLC
        // instead of the public property was surprising.
        // Anyway, it works. :-P
        $dql = "SELECT t FROM App\Entity\Term t
        LEFT JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.WoTextLC = :val";
        $query = $this->manager
               ->createQuery($dql)
               ->setParameter('langid', $lang->getLgID())
               ->setParameter('val', mb_strtolower($value));
        $terms = $query->getResult();

        if (count($terms) == 0)
            return null;
        return $terms[0];
    }

}