<?php

namespace App\Repository;

use App\Entity\Book;
use App\Domain\BookStats;
use App\Utils\DataTablesSqliteQuery;
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

    public function save(Book $entity): void
    {
        $isnew = ($entity->getID() == null);
        if ($entity->isArchived()) {
            foreach ($entity->getTexts() as $t)
                $t->setArchived(true);
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        if (($isnew || $entity->needsFullParse) && !$entity->isArchived()) {
            $entity->fullParse();
            $entity->needsFullParse = false;
        }
    }

    public function remove(Book $entity, bool $flush = false): void
    {
        // THIS IS REQUIRED.  Without the pragma, the deletes don't
        // propagate throughout the database foreign key cascade
        // deletes.
        //
        // NOTE it may be better to generalize this, per
        // https://stackoverflow.com/questions/22924444/,
        //   foreign-key-constraints-does-not-work-with-doctrine-symfony-and-sqlite
        //   (famas23's answer).
        $this->getEntityManager()->getConnection()->exec('PRAGMA foreign_keys = ON');

        $this->getEntityManager()->remove($entity);

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
          BkTitle || case
            when currtext.TxID is null then ''
            else ' (' || currtext.TxOrder || '/' || pagecnt.c || ')'
          end as BkTitle,
          pagecnt.c as PageCount,
          BkArchived,
          tags.taglist AS TagList,
          case when ifnull(b.BkWordCount, 0) = 0 then 'n/a' else b.BkWordCount end as WordCount,
          c.distinctterms as DistinctCount,
          c.distinctunknowns as UnknownCount,
          c.unknownpercent as UnknownPercent

          FROM books b
          INNER JOIN languages on LgID = b.BkLgID
          LEFT OUTER JOIN texts currtext on currtext.TxID = BkCurrentTxID
          INNER JOIN (
            select TxBkID, count(TxID) as c from texts
            group by TxBkID
          ) pagecnt on pagecnt.TxBkID = b.BkID
          LEFT OUTER JOIN bookstats c on c.BkID = b.BkID
          LEFT OUTER JOIN (
            SELECT BtBkID as BkID, GROUP_CONCAT(T2Text, ', ') AS taglist
            FROM
            (
              select BtBkID, T2Text
              from booktags bt
              INNER JOIN tags2 t2 on t2.T2ID = bt.BtT2ID
              ORDER BY T2Text
            ) tagssrc
            GROUP BY BtBkID
          ) AS tags on tags.BkID = b.BkID

          WHERE b.BkArchived = $archived";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesSqliteQuery::getData($base_sql, $parameters, $conn);
    }

}
