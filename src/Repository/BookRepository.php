<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    private function exec_sql(string $sql): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();
    }

    private function removeTi2s(int $bookid): void
    {
        $this->exec_sql("delete from textitems2 where Ti2TxID in (select TxID from texts where TxBkID = $bookid)");
    }

    private function removeSentences(int $bookid): void
    {
        $this->exec_sql("delete from sentences where SeTxID in (select TxID from texts where TxBkID = $bookid)");
    }

    public function save(Book $entity, bool $flush = false): void
    {
        // Books only need to be parsed when first saved
        // (... actually, not true, they only need to be parsed when
        // first read!).
        $isnew = ($entity->getId() == null);

        if ($entity->isArchived()) {
            foreach ($entity->getTexts() as $t)
                $t->setArchived(true);
        }

        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            if ($isnew) {
                foreach ($entity->getTexts() as $t)
                    $t->parse();
            }
            if ($entity->isArchived()) {
                $this->removeTi2s($entity->getId());
            }
        }
    }

    public function remove(Book $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        $this->removeTi2s($entity->getId());
        $this->removeSentences($entity->getId());

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters, $archived = false) {

        // Required, can't interpolate a bool in the sql string.
        $archived = $archived ? 'true' : 'false';

        $base_sql = "SELECT
          b.BkID As BkID,
          LgName,
          BkTitle,
          BkArchived,
          tags.taglist AS TagList,
          CONCAT(c.distinctterms, ' / ', c.sUnk) as TermStats,
          c.wordcount as WordCount,
          c.sUnk as Unknown,
          c.s1 + c.s2 as Learn1_2,
          c.s3 + c.s4 as Learn3_4,
          c.s5 as Learn5,
          c.sWkn as WellKnown

          FROM Books b
          INNER JOIN languages on LgID = b.BkLgID
          LEFT OUTER JOIN (
            select
              t.TxBkID,
              sum(c.distinctterms) as distinctterms,
              sum(c.sUnk) as sUnk,
              sum(c.wordcount) as wordcount,
              sum(c.s1) as s1,
              sum(c.s2) as s2,
              sum(c.s3) as s3,
              sum(c.s4) as s4,
              sum(c.s5) as s5,
              sum(c.sWkn) as sWkn
            from
            texts t
            left outer join textstatscache c on c.TxID = t.TxID
            group by t.TxBkID
          ) as c on c.TxBkID = b.BkID
          LEFT OUTER JOIN (
            SELECT BtBkID as BkID, GROUP_CONCAT(T2Text ORDER BY T2Text SEPARATOR ', ') AS taglist
            FROM
            booktags bt
            INNER JOIN tags2 t2 on t2.T2ID = bt.BtT2ID
            GROUP BY BtBkID
          ) AS tags on tags.BkID = b.BkID

          WHERE b.BkArchived = $archived";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesMySqlQuery::getData($base_sql, $parameters, $conn);
    }

}
