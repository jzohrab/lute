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

    private LanguageRepository $lang_repo;

    public function __construct(ManagerRegistry $registry, LanguageRepository $langrepo)
    {
        parent::__construct($registry, Term::class);
        $this->lang_repo = $langrepo;
    }

    public function save(Term $entity, bool $flush = false): void
    {
        // If the term's parent is new, throw some data into it.
        $parent = $this->findOrCreateParent($entity);
        $entity->setParent($parent);
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Convert parent_text text box content back into a real Term
     * instance, creating a new Term if needed.
     */
    private function findOrCreateParent(Term $entity): ?Term
    {
        $pt = $entity->getParentText();
        if ($pt == null || $pt == '')
            return null;

        if (is_null($entity->getLanguage())) {
            throw new \Exception('Language not set for Entity?');
        }

        $p = $this->findTermInLanguage($pt, $entity->getLanguage()->getLgID());

        if ($p !== null)
            return $p;

        $p = new Term();
        $p->setText($pt);
        $p->setLanguage($entity->getLanguage());
        $p->setStatus($entity->getStatus());
        $p->setTranslation($entity->getTranslation());
        $p->setSentence($entity->getSentence());
        foreach ($entity->getTermTags() as $termtag) {
            /**
             * @psalm-suppress InvalidArgument
             */
            $p->addTermTag($termtag);
        }
        return $p;
    }


    public function remove(Term $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            // TODO:mvp Remove textitem2 associations
            $this->getEntityManager()->flush();
        }
    }

    public function findTermInLanguage(string $value, int $langid): ?Term
    {
        // Using Doctrine Query Language --
        // Interesting, but am not totally confident with it.
        // e.g. That I had to use the private field WoTextLC
        // instead of the public property was surprising.
        // Anyway, it works. :-P
        $dql = "SELECT t FROM App\Entity\Term t
        LEFT JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.WoTextLC = :val";
        $em = $this->getEntityManager();
        $query = $em
               ->createQuery($dql)
               ->setParameter('langid', $langid)
               ->setParameter('val', mb_strtolower($value));
        $terms = $query->getResult();

        if (count($terms) == 0)
            return null;
        return $terms[0];
    }

    public function findByTextMatchInLanguage(string $value, int $langid, int $maxResults = 50): array
    {
        $search = mb_strtolower(trim($value));
        if ($search == '')
            return [];
        $search = '%' . $search . '%';

        $dql = "SELECT t FROM App\Entity\Term t
        JOIN App\Entity\Language L WITH L = t.language
        WHERE L.LgID = :langid AND t.WoTextLC LIKE :search
        ORDER BY t.WoTextLC";
        $em = $this->getEntityManager();
        $query = $em
               ->createQuery($dql)
               ->setParameter('langid', $langid)
               ->setParameter('search', $search)
               ->setMaxResults($maxResults);
        return $query->getResult();
    }


    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters) {

        $base_sql = "SELECT
w.WoID as WoID, LgName, WoText as WoText, ifnull(tags.taglist, '') as TagList, w.WoStatus
FROM
words w
INNER JOIN languages L on L.LgID = w.WoLgID

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



    /******************************************/
    // Loading a Term for the reading pane.
    //
    // Loading is rather complex.  It can be done by the Term ID (aka
    // the 'wid') or the textid and position in the text (the 'tid'
    // and 'ord'), or it might be a brand new multi-word term
    // (specified by the 'text').  The integration tests cover these
    // scenarios in /hopefully/ enough detail.

    /**
     * Get fully populated Term from database, or create a new one with available data.
     *
     * @param wid  int    WoID, an actual ID, or 0 if new.
     * @param tid  int    TxID, text ID
     * @param ord  int    Ti2Order, the order in the text
     * @param text string Multiword text (overrides tid/ord text)
     *
     * @return Term
     */
    public function load(int $wid = 0, int $tid = 0, int $ord = 0, string $text = ''): Term
    {
        $ret = null;
        if ($wid > 0 && ($text == '' || $text == '-')) {
            // Use wid, *provided that there is no text specified*.
            // If there is, the user has mousedown-drag created
            // a new multiword term.
            $ret = $this->find($wid);
        }
        elseif ($text != '') {
            $language = $this->getTextLanguage($tid);
            $ret = $this->loadFromText($text, $language);
        }
        elseif ($tid != 0 && $ord != 0) {
            $language = $this->getTextLanguage($tid);
            $ret = $this->loadFromTidAndOrd($tid, $ord, $language);
        }
        else {
            throw new \Exception("Out of options to search for term");
        }

        if ($ret->getSentence() == null && $tid != 0 && $ord != 0) {
            $s = $this->findSentence($tid, $ord);
            $ret->setSentence($s);
        }

        return $ret;
    }

    private function getTextLanguage($tid): ?Language {
        $sql = "SELECT TxLgID FROM texts WHERE TxID = {$tid}";
        $record = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql)
            ->fetchAssociative();
        if (! $record) {
            throw new \Exception("no record for tid = $tid ???");
        }
        $lang = $this->lang_repo->find((int) $record['TxLgID']);
        return $lang;
    }
    
    /**
     * Get baseline data from tid and ord.
     *
     * @return Term.
     */
    private function loadFromTidAndOrd($tid, $ord, Language $lang): ?Term {
        $sql = "SELECT ifnull(WoID, 0) as WoID,
          Ti2Text AS t
          FROM textitems2
          LEFT OUTER JOIN words on WoTextLC = Ti2TextLC
          WHERE Ti2TxID = {$tid} AND Ti2WordCount = 1 AND Ti2Order = {$ord}";
        $record = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql)
            ->fetchAssociative();
        if (! $record) {
            throw new \Exception("no matching textitems2 for tid = $tid , ord = $ord");
        }

        $wid = (int) $record['WoID'];
        if ($wid > 0) {
            return $this->find($wid);
        }

        $t = new Term();
        $t->setText($record['t']);
        $t->setLanguage($lang);
        return $t;
    }


    private function loadFromText(string $text, Language $lang): Term {
        $textlc = mb_strtolower($text);
        $t = $this->findTermInLanguage($text, $lang->getLgID());
        if (null != $t)
            return $t;

        $t = new Term();
        $t->setText($text);
        $t->setLanguage($lang);
        return $t;
    }


    private function findSentence($tid, $ord) : string {
        $sql = "select SeText
           from sentences
           INNER JOIN textitems2 on Ti2SeID = SeID
           WHERE Ti2TxID = :tid and Ti2Order = :ord";
        $params = [ "tid" => $tid, "ord" => $ord ];
        return $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, $params)
            ->fetchNumeric()[0];
  }

}
