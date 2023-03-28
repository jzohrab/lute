<?php

namespace App\Repository;

use App\Entity\Text;
use App\Entity\Sentence;
use App\Entity\TextItem;
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

    private function removeSentences(int $textid): void
    {
        $this->exec_sql("delete from sentences where SeTxID = $textid");
    }

    public function save(Text $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        $tid = $entity->getId();
        if ($flush) {
            $this->getEntityManager()->flush();
            if (! $entity->isArchived()) {
                $entity->parse();
            }
        }
    }

    public function remove(Text $entity, bool $flush = false): void
    {
        $textid = $entity->getID();
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            $this->removeSentences($textid);
        }
    }


    private function get_prev_or_next(Text $text, int $offset = 1, bool $getprev = true) {
        $op = $getprev ? " <= " : " >= ";
        $sortorder = $getprev ? " desc " : "";
        $bkid = $text->getBook()->getId();
        $useoffset = $offset;
        if ($getprev)
            $useoffset = -1 * $useoffset;
        $targetorder = $text->getOrder() + $useoffset;
        if ($text->getOrder() > 1 && $targetorder < 1)
            $targetorder = 1;

        // DQL can be -- non-intuitive.
        // Leaving this for now b/c it works, but I'd prefer regular SQL.
        $dql = "SELECT t FROM App\Entity\Text t
        JOIN App\Entity\Book b WITH b = t.book
        WHERE b.BkID = $bkid AND t.TxOrder $op $targetorder
        ORDER BY t.TxOrder $sortorder";

        $query = $this->getEntityManager()
               ->createQuery($dql)
               ->setMaxResults(1);
        $texts = $query->getResult();

        if (count($texts) == 0)
            return null;
        return $texts[0];
    }

    
    public function get_prev_next(Text $text) {
        $p = $this->get_prev_or_next($text, 1, true);
        $n = $this->get_prev_or_next($text, 1, false);
        return [ $p, $n ];
    }

    public function get_prev_next_by_10(Text $text) {
        $p = $this->get_prev_or_next($text, 10, true);
        $n = $this->get_prev_or_next($text, 10, false);
        return [ $p, $n ];
    }

}
