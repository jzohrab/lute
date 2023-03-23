<?php

namespace App\Repository;

use App\Entity\Text;
use App\DTO\TextToken;
use App\Entity\TextSentence;
use App\Entity\Term;
use App\Entity\Sentence;
use App\Entity\TextItem;
use App\Entity\Language;
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
