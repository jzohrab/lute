<?php

namespace App\Repository;

use App\Entity\Term;
use App\Entity\Text;
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
        $em = $this->getEntityManager();
        $em->flush();
    }

    public function clear(): void
    {
        $em = $this->getEntityManager();
        $em->clear();
    }

    public function detach(Term $t): void
    {
        $this->getEntityManager()->detach($t);
    }

    public function stopSqlLog(): void
    {
        $this->getEntityManager()->
            getConnection()->
            getConfiguration()->
            setSQLLogger(null);
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
               ->setParameter('val', $specification->getLanguage()->getParser()->getLowercase($specification->getText()));
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

        // Exact match goes to top.
        $ret = array_filter($raw, fn($r) => $r->getTextLC() == $search);

        // Parents in next.
        $parents = array_filter(
            $raw,
            fn($r) => $r->getChildren()->count() > 0 && $r->getTextLC() != $search
        );
        $ret = array_merge($ret, $parents);

        $remaining = array_filter(
            $raw,
            fn($r) => $r->getTextLC() != $search && $r->getChildren()->count() == 0
        );
        return array_merge($ret, $remaining);
    }


    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters) {

        $base_sql = "SELECT
0 as chk, w.WoID as WoID, LgName, L.LgID as LgID, w.WoText as WoText, parents.parentlist as ParentText, w.WoTranslation,
replace(wi.WiSource, '.jpeg', '') as WiSource,
ifnull(tags.taglist, '') as TagList,
StText,
StID
FROM
words w
INNER JOIN languages L on L.LgID = w.WoLgID
INNER JOIN statuses S on S.StID = w.WoStatus
LEFT OUTER JOIN (
  SELECT WpWoID as WoID, GROUP_CONCAT(PText, ', ') AS parentlist
  FROM
  (
    select WpWoID, WoText as PText
    from wordparents wp
    INNER JOIN words on WoID = WpParentWoID
    order by WoText
  ) parentssrc
  GROUP BY WpWoID
) AS parents on parents.WoID = w.WoID
LEFT OUTER JOIN (
  SELECT WtWoID as WoID, GROUP_CONCAT(TgText, ', ') AS taglist
  FROM
  (
    select WtWoID, TgText
    from wordtags wt
    INNER JOIN tags t on t.TgID = wt.WtTgID
    order by TgText
  ) tagssrc
  GROUP BY WtWoID
) AS tags on tags.WoID = w.WoID
LEFT OUTER JOIN wordimages wi on wi.WiWoID = w.WoID
";

        // Extra search filters passed in as data from term/index.html.twig.
        $filtParentsOnly = $parameters['filtParentsOnly'];
        $filtAgeMin = trim($parameters['filtAgeMin']);
        $filtAgeMax = trim($parameters['filtAgeMax']);
        $filtStatusMin = intval($parameters['filtStatusMin']);
        $filtStatusMax = intval($parameters['filtStatusMax']);
        $filtIncludeIgnored = $parameters['filtIncludeIgnored'];

        $wheres = [ "1 = 1" ];
        if ($filtParentsOnly == 'true')
            $wheres[] = "parents.parentlist IS NULL";
        if ($filtAgeMin != "") {
            $filtAgeMin = intval('0' . $filtAgeMin);
            $wheres[] = "cast(julianday('now') - julianday(w.wocreated) as int) >= $filtAgeMin";
        }
        if ($filtAgeMax != "") {
            $filtAgeMax = intval('0' . $filtAgeMax);
            $wheres[] = "cast(julianday('now') - julianday(w.wocreated) as int) <= $filtAgeMax";
        }

        $statuswheres = [ "StID <> 98" ];  // Exclude "ignored" terms at first.
        if ($filtStatusMin > 0) {
            $statuswheres[] = "StID >= $filtStatusMin";
        }
        if ($filtStatusMax > 0) {
            $statuswheres[] = "StID <= $filtStatusMax";
        }
        $statuswheres = implode(' AND ', $statuswheres);
        if ($filtIncludeIgnored == 'true') {
            $statuswheres = "(({$statuswheres}) OR StID = 98)";
        }
        $wheres[] = $statuswheres;

        $where = implode(' AND ', $wheres);

        $full_base_sql = $base_sql . ' WHERE ' . $where;

        $conn = $this->getEntityManager()->getConnection();
        return DataTablesSqliteQuery::getData($full_base_sql, $parameters, $conn);
    }

}
