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
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
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

}
