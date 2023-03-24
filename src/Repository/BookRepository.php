<?php

namespace App\Repository;

use App\Entity\Book;
use App\Domain\BookStats;
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

    private function removeSentences(int $bookid): void
    {
        $this->exec_sql("delete from sentences where SeTxID in (select TxID from texts where TxBkID = $bookid)");
    }

    public function save(Book $entity, bool $flush = false): void
    {
        if ($entity->isArchived()) {
            foreach ($entity->getTexts() as $t)
                $t->setArchived(true);
        }

        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Book $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
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
          COALESCE(currtext.TxTitle, BkTitle) as BkTitle,
          pagecnt.c as PageCount,
          BkArchived,
          tags.taglist AS TagList,
          c.wordcount as WordCount,
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
