<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TermRepository;
use App\Repository\TextItemRepository;

class Dictionary {

    private EntityManagerInterface $manager;
    private array $pendingTerms;

    public function __construct(
        EntityManagerInterface $manager
    ) {
        $this->manager = $manager;
        $this->pendingTerms = array();
    }

    public function add(Term $term, bool $flush = true) {
        $parent = $this->findOrCreateParent($term);
        $term->setParent($parent);

        $this->pendingTerms[] = $term;
        $this->manager->persist($term);
        if ($flush) {
            $this->flush();
        }
    }

    public function flush() {
        // dump('flushing ' . count($this->pendingTerms) . ' terms.');
        $this->manager->flush();
        TextItemRepository::bulkMap($this->pendingTerms);
        $this->pendingTerms = array();
    }

    /**
     * Convert parent_text into a real Term, creating a new Term if
     * needed.
     */
    private function findOrCreateParent(Term $entity): ?Term
    {
        $pt = $entity->getParentText();
        if ($pt == null || $pt == '')
            return null;

        if (is_null($entity->getLanguage())) {
            throw new \Exception('Language not set for Entity?');
        }

        $p = $this->find($pt, $entity->getLanguage());

        if ($p !== null)
            return $p;

        $p = new Term();
        $p->setText($pt);
        $p->setLanguage($entity->getLanguage());
        $p->setStatus($entity->getStatus());
        $p->setTranslation($entity->getTranslation());
        $p->setSentence($entity->getSentence());
        foreach ($entity->getTermTags() as $termtag) {
            /**
             * @psalm-suppress InvalidArgument
             */
            $p->addTermTag($termtag);
        }
        return $p;
    }


    public function remove(Term $term): void
    {
        $this->manager->remove($term);
        TextItemRepository::unmapForTerm($term);
        $this->manager->flush();
    }


    /**
     * Find a term by an exact match.
     */
    public function find(string $value, Language $lang) {
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

    /**
     * Find Terms by matching text.
     */
    public function findMatches(string $value, Language $lang, int $maxResults = 50): array
    {
        $search = mb_strtolower(trim($value));
        if ($search == '')
            return [];
        $search = '%' . $search . '%';

        $dql = "SELECT t FROM App\Entity\Term t
        JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.WoTextLC LIKE :search
        ORDER BY t.WoTextLC";
        $query = $this->manager
               ->createQuery($dql)
               ->setParameter('langid', $lang->getLgID())
               ->setParameter('search', $search)
               ->setMaxResults($maxResults);
        return $query->getResult();
    }
}