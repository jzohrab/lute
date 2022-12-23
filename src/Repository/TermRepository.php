<?php

namespace App\Repository;

use App\Entity\Term;
use App\Entity\Language;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Term>
 *
 * @method Term|null find($id, $lockMode = null, $lockVersion = null)
 * @method Term|null findOneBy(array $criteria, array $orderBy = null)
 * @method Term[]    findAll()
 * @method Term[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Term::class);
    }

    public function save(Term $entity, bool $flush = false): void
    {
        // If the term's parent is new, throw some data into it.
        $parent = $this->findOrCreateParent($entity);
        $entity->setParent($parent);
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Convert parent_text text box content back into a real Term
     * instance, creating a new Term if needed.
     */
    private function findOrCreateParent(Term $entity): ?Term
    {
        $pt = $entity->getParentText();
        if ($pt == null || $pt == '')
            return null;

        if (is_null($entity->getLanguage())) {
            throw new \Exception('Language not set for Entity?');
        }

        $p = $this->findTermInLanguage($pt, $entity->getLanguage());

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


    public function remove(Term $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            // TODO:mvp Remove textitem2 associations
            $this->getEntityManager()->flush();
        }
    }


    public function findTermInLanguage(string $value, Language $lang): ?Term
    {
        // Using Doctrine Query Language --
        // Interesting, but am not totally confident with it.
        // e.g. That I had to use the private field WoTextLC
        // instead of the public property was surprising.
        // Anyway, it works. :-P
        $dql = "SELECT t FROM App\Entity\Term t
        LEFT JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.WoTextLC = :val";
        $em = $this->getEntityManager();
        $query = $em
               ->createQuery($dql)
               ->setParameter('langid', $lang->getLgID())
               ->setParameter('val', mb_strtolower($value));
        $terms = $query->getResult();

        if (count($terms) == 0)
            return null;
        return $terms[0];
    }

    public function findByTextMatchInLanguage(string $value, int $langid, int $maxResults = 50): array
    {
        $search = mb_strtolower(trim($value));
        if ($search == '')
            return [];
        $search = '%' . $search . '%';

        $dql = "SELECT t FROM App\Entity\Term t
        JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.WoTextLC LIKE :search
        ORDER BY t.WoTextLC";
        $em = $this->getEntityManager();
        $query = $em
               ->createQuery($dql)
               ->setParameter('langid', $langid)
               ->setParameter('search', $search)
               ->setMaxResults($maxResults);
        return $query->getResult();
    }


    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters) {

        $base_sql = "SELECT
w.WoID as WoID, LgName, WoText as WoText, ifnull(tags.taglist, '') as TagList, w.WoStatus
FROM
words w
INNER JOIN languages L on L.LgID = w.WoLgID

LEFT OUTER JOIN (
  SELECT WtWoID as WoID, GROUP_CONCAT(TgText ORDER BY TgText SEPARATOR ', ') AS taglist
  FROM
  wordtags wt
  INNER JOIN tags t on t.TgID = wt.WtTgID
  GROUP BY WtWoID
) AS tags on tags.WoID = w.WoID
";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesMySqlQuery::getData($base_sql, $parameters, $conn);
    }

}
