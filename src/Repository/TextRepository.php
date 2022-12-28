<?php

namespace App\Repository;

use App\Entity\Text;
use App\Entity\Sentence;
use App\Entity\TextItem;
use App\Domain\Parser;
use App\Domain\TextStatsCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Text>
 *
 * @method Text|null find($id, $lockMode = null, $lockVersion = null)
 * @method Text|null findOneBy(array $criteria, array $orderBy = null)
 * @method Text[]    findAll()
 * @method Text[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Text::class);
    }

    private function removeSentencesAndWords(int $textid): void
    {
        // TODO - if create sentence and textitem entities, find and delete?
        $conn = $this->getEntityManager()->getConnection();
        $sqls = [
            "delete from sentences where SeTxID = $textid",
            "delete from textitems2 where Ti2TxID = $textid"
        ];
        foreach ($sqls as $sql) {
            $stmt = $conn->prepare($sql);
            $stmt->executeQuery();
        }
    }

    public function save(Text $entity, bool $flush = false, bool $parseTexts = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            $this->removeSentencesAndWords($entity->getID());
            TextStatsCache::markStale([$entity->getID()]);

            if (! $entity->isArchived() ) {
                $langid = $entity->getLanguage()->getLgID();

                if ($parseTexts) {
                    Parser::parse($entity);
                }
            }
        }
    }

    public function remove(Text $entity, bool $flush = false): void
    {
        $textid = $entity->getID();
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            $this->removeSentencesAndWords($textid);
        }
    }


    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters, $archived = false) {

        // Required, can't interpolate a bool in the sql string.
        $archived = $archived ? 'true' : 'false';

        $base_sql = "SELECT
          t.TxID As TxID,
          LgName,
          TxTitle,
          TxArchived,
          tags.taglist AS TagList,
          CONCAT(c.distinctterms, ' / ', c.sUnk) as TermStats,
          c.wordcount as WordCount,
          c.sUnk as Unknown,
          c.s1 + c.s2 as Learn1_2,
          c.s3 + c.s4 as Learn3_4,
          c.s5 as Learn5,
          c.sWkn as WellKnown

          FROM texts t
          INNER JOIN languages on LgID = t.TxLgID
          LEFT OUTER JOIN textstatscache c on c.TxID = t.TxID

          LEFT OUTER JOIN (
            SELECT TtTxID as TxID, GROUP_CONCAT(T2Text ORDER BY T2Text SEPARATOR ', ') AS taglist
            FROM
            texttags tt
            INNER JOIN tags2 t2 on t2.T2ID = tt.TtT2ID
            GROUP BY TtTxID
          ) AS tags on tags.TxID = t.TxID

          WHERE t.TxArchived = $archived";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesMySqlQuery::getData($base_sql, $parameters, $conn);
    }


    private function get_prev_or_next(Text $text, bool $getprev = true) {
        $op = $getprev ? " < " : " > ";
        $sortorder = $getprev ? " desc " : "";

        // DQL can be -- non-intuitive.
        // Leaving this for now b/c it works, but I'd prefer regular SQL.
        $dql = "SELECT t FROM App\Entity\Text t
        JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.TxID $op :currid
        ORDER BY t.TxID $sortorder";

        $query = $this->getEntityManager()
               ->createQuery($dql)
               ->setParameter('langid', $text->getLanguage()->getLgID())
               ->setParameter('currid', $text->getID())
               ->setMaxResults(1);
        $texts = $query->getResult();

        if (count($texts) == 0)
            return null;
        return $texts[0];
    }

    
    public function get_prev_next(Text $text) {
        $p = $this->get_prev_or_next($text, true);
        $n = $this->get_prev_or_next($text, false);
        return [ $p, $n ];
    }


}
