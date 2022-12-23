<?php

namespace App\Repository;

use App\Entity\Text;
use App\Entity\Sentence;
use App\Entity\TextItem;
use App\Domain\Parser;
use App\Domain\ExpressionUpdater;
use App\Domain\TextStatsCache;
use Doctrine\ORM\EntityManagerInterface;
 

class ReadingRepository
{
    private EntityManagerInterface $manager;
    private TermRepository $term_repo;
    private LanguageRepository $langrepo;

    public function __construct(
        EntityManagerInterface $manager,
        TermRepository $term_repo,
        LanguageRepository $langrepo
    )
    {
        $this->manager = $manager;
        $this->term_repo = $term_repo;
        $this->langrepo = $langrepo;
    }

    public function getTextItems(Text $entity, int $woid = null) {
        $textid = $textid = $entity->getID();
        if ($textid == null)
            return [];

        $where = [ "Ti2TxID = $textid" ];
        if ($woid != null)
            $where[] = "w.WoID = $woid";
        $where = implode(' AND ', $where);

        $sql = "SELECT
           $textid AS TextID,
           Ti2WordCount AS WordCount,
           Ti2Text AS Text,
           Ti2TextLC AS TextLC,
           Ti2Order AS `Order`,
           Ti2SeID AS SeID,
           CASE WHEN Ti2WordCount>0 THEN 1 ELSE 0 END AS IsWord,
           CHAR_LENGTH(Ti2Text) AS TextLength,
           w.WoID,
           w.WoText,
           w.WoStatus,
           w.WoTranslation,
           w.WoRomanization,
           IF (wordtags IS NULL, '', CONCAT('[', wordtags, ']')) as Tags,

           pw.WoID as ParentWoID,
           pw.WoTextLC as ParentWoTextLC,
           pw.WoTranslation as ParentWoTranslation,
           IF (parenttags IS NULL, '', CONCAT('[', parenttags, ']')) as ParentTags

           FROM textitems2
           LEFT JOIN words AS w ON Ti2WoID = w.WoID
           LEFT JOIN (
             SELECT
             WtWoID,
             GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', ') as wordtags
             FROM wordtags
             INNER JOIN tags ON TgID = WtTgID
             GROUP BY WtWoID
           ) wordtaglist on wordtaglist.WtWoID = w.WoID

           LEFT JOIN wordparents ON wordparents.WpWoID = w.WoID
           LEFT JOIN words AS pw on pw.WoID = wordparents.WpParentWoID
           LEFT JOIN (
             SELECT
             wordparents.WpWoID,
             GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', ') as parenttags
             FROM wordtags
             INNER JOIN tags ON TgID = WtTgID
             INNER JOIN wordparents on wordparents.WpParentWoID = wordtags.WtWoID
             GROUP BY WpWoID
           ) parenttaglist on parenttaglist.WpWoID = w.WoID

           WHERE $where
           ORDER BY Ti2Order asc, Ti2WordCount desc";

        $conn = $this->manager->getConnection();
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();
        $rows = $res->fetchAllAssociative();

        $textitems = [];
        foreach ($rows as $row) {
            $t = new TextItem();
            foreach ($row as $key => $val) {
                $t->$key = $val;
            }
            $intkeys = [ 'TextID', 'WordCount', 'Order', 'SeID', 'IsWord', 'TextLength', 'WoID', 'WoStatus', 'ParentWoID' ];
            foreach ($intkeys as $key) {
                $t->$key = intval($t->$key);
            }
            $textitems[] = $t;
        }

        return $textitems;
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
            $ret = $this->term_repo->find($wid);
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
        $t = $this->term_repo->findTermInLanguage($text, $lang->getLgID());
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


    public function save(Term $term, bool $flush = false): void {
        $this->term_repo->save($term, $flush);
    }

}
