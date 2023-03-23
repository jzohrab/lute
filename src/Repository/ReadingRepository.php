<?php

namespace App\Repository;

use App\Entity\Text;
use App\DTO\TextToken;
use App\Entity\TextSentence;
use App\Entity\Term;
use App\Entity\Sentence;
use App\Entity\TextItem;
use App\Entity\Language;
use App\Repository\TextItemRepository;
use App\Domain\Dictionary;
use Doctrine\ORM\EntityManagerInterface;
 

class ReadingRepository
{
    private EntityManagerInterface $manager;
    private Dictionary $dictionary;
    private TermRepository $term_repo;
    private LanguageRepository $lang_repo;

    public function __construct(
        EntityManagerInterface $manager,
        TermRepository $term_repo,
        LanguageRepository $lang_repo
    )
    {
        $this->manager = $manager;
        $this->dictionary = new Dictionary($term_repo);
        $this->term_repo = $term_repo;
        $this->lang_repo = $lang_repo;
    }

    public function getSentences(Text $t): array {
        $textid = $t->getID();
        if ($textid == null)
            return [];

        $sql = "select
          TokSentenceNumber,
          CONCAT(0xE2808B, GROUP_CONCAT(TokText order by TokOrder SEPARATOR 0xE2808B), 0xE2808B) as SeText
          FROM texttokens
          where TokTxID = $textid
          group by TokSentenceNumber";

        // dump($sql);
        $conn = $this->manager->getConnection();
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();
        $rows = $res->fetchAllAssociative();

        $ret = [];
        foreach ($rows as $row) {
            $s = new TextSentence();
            $s->SeID = intval($row['TokSentenceNumber']);
            $s->SeText = $row['SeText'];
            $ret[] = $s;
        }
        return $ret;
    }

    public function getTextTokens(Text $t): array {
        $textid = $t->getID();
        if ($textid == null)
            return [];

        $sql = "select
          TokSentenceNumber,
          TokOrder,
          TokIsWord,
          TokText
          from texttokens
          where toktxid = $textid
          order by TokSentenceNumber, TokOrder";

        $conn = $this->manager->getConnection();
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();
        $rows = $res->fetchAllAssociative();

        $ret = [];
        foreach ($rows as $row) {
            $tok = new TextToken();
            $tok->TokSentenceNumber = intval($row['TokSentenceNumber']);
            $tok->TokOrder = intval($row['TokOrder']);
            $tok->TokIsWord = intval($row['TokIsWord']);
            $tok->TokText = $row['TokText'];
            $ret[] = $tok;
        }
        return $ret;
    }

    public function getTermsInText(Text $text) {
        return $this->term_repo->findTermsInText($text);
    }

    public function getTextItems(Text $entity, int $woid = null) {
        $textid = $entity->getID();
        if ($textid == null)
            return [];

        $where = [ "Ti2TxID = $textid" ];
        if ($woid != null)
            $where[] = "w.WoID = $woid";
        $where = implode(' AND ', $where);

        $sql = "SELECT
           $textid AS TextID,
           Ti2LgID as LangID,
           Ti2WordCount AS WordCount,
           IFNULL(w.WoTokenCount, 1) AS TokenCount,
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
           wi.WiSource as ImageSource,

           pw.WoID as ParentWoID,
           pw.WoTextLC as ParentWoTextLC,
           pw.WoTranslation as ParentWoTranslation,
           IF (parenttags IS NULL, '', CONCAT('[', parenttags, ']')) as ParentTags,
           pwi.WiSource as ParentImageSource

           FROM textitems2
           LEFT JOIN words AS w ON Ti2WoID = w.WoID
           LEFT JOIN wordimages wi on wi.WiWoID = w.WoID
           LEFT JOIN (
             SELECT
             WtWoID,
             GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', ') as wordtags
             FROM wordtags
             INNER JOIN tags ON TgID = WtTgID
             GROUP BY WtWoID
           ) wordtaglist on wordtaglist.WtWoID = w.WoID

           LEFT JOIN wordparents ON wordparents.WpWoID = w.WoID
           LEFT JOIN wordimages pwi on pwi.WiWoID = wordparents.WpParentWoID
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
            // Yuck. Oh well.
            $t = new TextItem();
            $t->TextID = intval($row['TextID']);
            $t->LangID = intval($row['LangID']);
            $t->WordCount = intval($row['WordCount']);
            $t->TokenCount = intval($row['TokenCount']);
            $t->Text = $row['Text'];
            $t->TextLC = $row['TextLC'];
            $t->Order = intval($row['Order']);
            $t->SeID = intval($row['SeID']);
            $t->IsWord = intval($row['IsWord']);
            $t->TextLength = intval($row['TextLength']);
            $t->WoID = intval($row['WoID']);
            $t->WoText = $row['WoText'];
            $t->WoStatus = intval($row['WoStatus']);
            $t->WoTranslation = $row['WoTranslation'];
            $t->WoRomanization = $row['WoRomanization'];
            $t->Tags = $row['Tags'];
            $t->ImageSource = $row['ImageSource'];
            $t->ParentWoID = intval($row['ParentWoID']);
            $t->ParentWoTextLC = $row['ParentWoTextLC'];
            $t->ParentWoTranslation = $row['ParentWoTranslation'];
            $t->ParentTags = $row['ParentTags'];
            $t->ParentImageSource = $row['ParentImageSource'];

            $textitems[] = $t;
        }

        return $textitems;
    }


    /**
     * Get fully populated Term from database, or create a new one.
     *
     * @param lid  int    LgID, the language ID
     * @param text string
     *
     * @return Term
     */
    public function load(int $lid, string $text): Term
    {
        $language = $this->lang_repo->find($lid);
        $textlc = mb_strtolower($text);
        $t = $this->dictionary->find($textlc, $language);
        if (null != $t)
            return $t;
        return new Term($language, $textlc);
    }


    public function save(Term $term): void {
        $this->dictionary->add($term);
    }

    public function remove(Term $term): void {
        $this->dictionary->remove($term);
    }

}
