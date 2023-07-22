<?php

namespace App\Repository;

use App\Entity\Text;
use App\DTO\TextToken;
use App\Entity\TextSentence;
use App\Entity\Term;
use App\Entity\Sentence;
use App\Entity\TextItem;
use App\Entity\Language;
use App\Domain\TermService;
use Doctrine\ORM\EntityManagerInterface;
 

class ReadingRepository
{
    private EntityManagerInterface $manager;
    private TermService $term_service;
    private TermRepository $term_repo;

    public function __construct(
        EntityManagerInterface $manager,
        TermRepository $term_repo
    )
    {
        $this->manager = $manager;
        $this->term_service = new TermService($term_repo);
        $this->term_repo = $term_repo;
    }

    public function getSentences(Text $t): array {
        $textid = $t->getID();
        if ($textid == null)
            return [];

        $sql = "select
          TokSentenceNumber,
          char(0x200B) || GROUP_CONCAT(TokText, char(0x200B)) || char(0x200B) as SeText
          FROM (
            select TokSentenceNumber, TokText
            from texttokens
            where TokTxID = $textid
            order by TokOrder
          ) src
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
          TokText,
          TokTextLC
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
            $tok->TokTextLC = $row['TokTextLC'];
            $ret[] = $tok;
        }
        return $ret;
    }

    public function getTermsInText(Text $text) {
        return $this->term_repo->findTermsInText($text);
    }

}
