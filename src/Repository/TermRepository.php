<?php

namespace App\Repository;

use App\Entity\Term;
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

    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters) {

        $base_sql = "SELECT
w.WoID as WoID, LgName, WoText as WoText, ifnull(tags.taglist, '') as TagList, StText
FROM
words w
INNER JOIN languages L on L.LgID = w.WoLgID
INNER JOIN statuses S on S.StID = w.WoStatus
LEFT OUTER JOIN (
  SELECT WtWoID as WoID, GROUP_CONCAT(TgText ORDER BY TgText SEPARATOR ', ') AS taglist
  FROM
  wordtags wt
  INNER JOIN tags t on t.TgID = wt.WtTgID
  GROUP BY WtWoID
) AS tags on tags.WoID = w.WoID
";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesMySqlQuery::getData($base_sql, $parameters, $conn);
    }

}
