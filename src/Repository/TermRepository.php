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


    public function findTermsInText(Text $t) {
        $wids = [];
        $conn = $this->getEntityManager()->getConnection();

        // Querying all words that match the text is very slow, so
        // breaking it up into two parts.

        // 1. Get all exact matches from the tokens.
        $lgid = $t->getLanguage()->getLgID();
        $sql = "select distinct WoID from words
            where wotextlc in (select LOWER(TokText) from texttokens where toktxid = {$t->getID()})
            and WoTokenCount = 1 and WoLgID = $lgid";
        $res = $conn->executeQuery($sql);
        while ($row = $res->fetchNumeric()) {
            $wids[] = $row[0];
        }

        // 2. Get multiword terms that likely match (don't bother
        // checking word boundaries).
        $sql = "select WoID from words
            where WoTokenCount > 1 AND WoLgID = $lgid AND
            instr(
              (select LOWER(TxText) from texts where TxID = {$t->getID()}),
              replace(WoTextLC, 0xE2808B, '')
            ) > 0";
        $res = $conn->executeQuery($sql);
        while ($row = $res->fetchNumeric()) {
            $wids[] = $row[0];
        }

        // Add any term parents, they might be needed when loading the
        // actual terms.
        $sql = "select WpParentWoID from wordparents where WpWoID in (?)";
        $res = $conn->executeQuery($sql, array($wids), array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY));
        while ($row = $res->fetchNumeric()) {
            $wids[] = $row[0];
        }
        
        $dql = "SELECT t, tt, ti, tp, tpt, tpi
          FROM App\Entity\Term t
          LEFT JOIN t.termTags tt
          LEFT JOIN t.images ti
          LEFT JOIN t.parents tp
          LEFT JOIN tp.termTags tpt
          LEFT JOIN tp.images tpi
          WHERE t.id in (:tids)";
        $query = $this->getEntityManager()
               ->createQuery($dql)
               ->setParameter('tids', $wids);
        $raw = $query->getResult();
        return $raw;
    }


    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters) {

        $base_sql = "SELECT
0 as chk, w.WoID as WoID, LgName, L.LgID as LgID, w.WoText as WoText, p.WoText as ParentText, w.WoTranslation, wi.WiSource, ifnull(tags.taglist, '') as TagList, StText
FROM
words w
INNER JOIN languages L on L.LgID = w.WoLgID
INNER JOIN statuses S on S.StID = w.WoStatus
LEFT OUTER JOIN wordparents on WpWoID = w.WoID
LEFT OUTER JOIN words p on p.WoID = WpParentWoID
LEFT OUTER JOIN (
  SELECT WtWoID as WoID, GROUP_CONCAT(TgText ORDER BY TgText SEPARATOR ', ') AS taglist
  FROM
  wordtags wt
  INNER JOIN tags t on t.TgID = wt.WtTgID
  GROUP BY WtWoID
) AS tags on tags.WoID = w.WoID
LEFT OUTER JOIN wordimages wi on wi.WiWoID = w.WoID
";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesMySqlQuery::getData($base_sql, $parameters, $conn);
    }

}
