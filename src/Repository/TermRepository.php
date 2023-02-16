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
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Term $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }


    /**
     * Find a term by an exact match with the specification (only
     * looks at Text and Language).
     */
    public function findBySpecification(Term $specification): ?Term {
        // Using Doctrine Query Language --
        // Interesting, but am not totally confident with it.
        // e.g. That I had to use the private field WoTextLC
        // instead of the public property was surprising.
        // Anyway, it works. :-P
        $dql = "SELECT t FROM App\Entity\Term t
        LEFT JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.WoTextLC = :val";
        $query = $this->getEntityManager()
               ->createQuery($dql)
               ->setParameter('langid', $specification->getLanguage()->getLgID())
               ->setParameter('val', mb_strtolower($specification->getText()));
        $terms = $query->getResult();

        if (count($terms) == 0)
            return null;
        return $terms[0];
    }

    /**
     * Find Terms by text.
     */
    public function findLikeSpecification(Term $specification, int $maxResults = 50): array
    {
        $search = mb_strtolower(trim($specification->getText() ?? ''));
        if ($search == '')
            return [];

        $dql = "SELECT t FROM App\Entity\Term t
        JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.WoTextLC LIKE :search
        ORDER BY t.WoTextLC";
        $query = $this->getEntityManager()
               ->createQuery($dql)
               ->setParameter('langid', $specification->getLanguage()->getLgID())
               ->setParameter('search', $search . '%')
               ->setMaxResults($maxResults);
        $raw = $query->getResult();

        $ret = [];

        // Exact match goes to top.
        for ($i = 0; $i < count($raw); $i++) {
            if ($raw[$i]->getTextLC() == $search) {
                $ret[] = $raw[$i];
                array_splice($raw, $i, 1);
            }
        }

        // Parent terms go in next.
        for ($i = 0; $i < count($raw); $i++) {
            if ($raw[$i]->getChildren()->count() > 0) {
                $ret[] = $raw[$i];
                array_splice($raw, $i, 1);
            }
        }

        // Re-add the rest.
        return array_merge($ret, $raw);
    }


    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters) {

        $base_sql = "SELECT
w.WoID as WoID, LgName, WoText as WoText, WoTranslation, ifnull(tags.taglist, '') as TagList, StText
FROM
words w
INNER JOIN languages L on L.LgID = w.WoLgID
INNER JOIN statuses S on S.StID = w.WoStatus
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
