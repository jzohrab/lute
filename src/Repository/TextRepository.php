<?php

namespace App\Repository;

use App\Entity\Text;
use App\Entity\Book;
use App\Entity\Sentence;
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

    private function exec_sql(string $sql): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();
    }

    public function save(Text $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        $tid = $entity->getId();
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Text $entity, bool $flush = false): void
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

        $textid = $entity->getID();
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    public function getTextAtPageNumber(Book $book, int $pagenum) {
        $bkid = $book->getID();
        $dql = "SELECT t FROM App\Entity\Text t
        JOIN App\Entity\Book b WITH b = t.book
        WHERE b.BkID = $bkid AND t.TxOrder = $pagenum";

        $query = $this->getEntityManager()
               ->createQuery($dql)
               ->setMaxResults(1);
        $texts = $query->getResult();

        if (count($texts) == 0)
            return null;
        return $texts[0];
    }

}
