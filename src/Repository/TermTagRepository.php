<?php

namespace App\Repository;

use App\Entity\TermTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TermTag>
 *
 * @method TermTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method TermTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method TermTag[]    findAll()
 * @method TermTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TermTag::class);
    }

    public function save(TermTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters) {
        $base_sql = "SELECT
          TgID,
          TgText,
          TgComment,
          ifnull(TermCount, 0) as TermCount
          FROM tags
          left join (
            select WtTgID,
            count(*) as TermCount
            from wordtags
            group by WtTgID
          ) src on src.WtTgID = TgID
        ";
        $conn = $this->getEntityManager()->getConnection();
        return DataTablesSqliteQuery::getData($base_sql, $parameters, $conn);
    }

    public function remove(TermTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByText($value): ?TermTag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.text = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOrCreateByText($value): ?TermTag
    {
        $t = $this->findByText($value);
        if ($t != null)
            return $t;
        $t = new TermTag();
        $t->setText($value);
        return $t;
    }

}
