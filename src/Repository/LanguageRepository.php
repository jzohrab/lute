<?php

namespace App\Repository;

use App\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Language>
 *
 * @method Language|null find($id, $lockMode = null, $lockVersion = null)
 * @method Language|null findOneBy(array $criteria, array $orderBy = null)
 * @method Language[]    findAll()
 * @method Language[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Language::class);
    }

    public function save(Language $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Language $entity, bool $flush = false): void
    {
        $lgid = $entity->getLgID();
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $conn = $this->getEntityManager()->getConnection();

            // TODO: fix_db_fk_cascade_delete
            // This is _extremely_ clumsy.  The sqlite db should have foreign key
            // cascade delete for entities, prevents bad db data.
            $sqls = [
                "delete from texttokens where TokTxID in (select TxID from texts inner join books on TxBkID = BkID where BkLgID = $lgid)",
                "delete from texts where TxBkID in (select BkID from books where BkLgID = $lgid)",
                "delete from books where BkLgID = $lgid",
                "delete from wordimages where WiWoID in (select WoID from words where WoLgID = $lgid)",
                "delete from wordparents where WpWoID in (select WoID from words where WoLgID = $lgid)",
                "delete from words where WoLgID = $lgid"
            ];
            foreach ($sqls as $sql) {
                $conn->executeQuery($sql);
            }

            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Language[] Returns an array of Language objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Language
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
