<?php

namespace App\Repository;

use App\Entity\TextTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TextTag>
 *
 * @method TextTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method TextTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method TextTag[]    findAll()
 * @method TextTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TextTag::class);
    }

    public function save(TextTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TextTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByText($value): ?TextTag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.text = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOrCreateByText($value): TextTag
    {
        $t = $this->findByText($value);
        if ($t != null)
            return $t;
        $t = new TextTag();
        $t->setText($value);
        return $t;
    }

//    /**
//     * @return TextTag[] Returns an array of TextTag objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TextTag
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
