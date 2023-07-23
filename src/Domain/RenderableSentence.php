<?php

namespace App\Domain;

use App\Entity\Text;
use App\Utils\Connection;
use App\DTO\TextToken;
use App\Repository\TermRepository;

class RenderableSentence
{

    public int $SeID;
    public array $textitems;

    public function __construct(int $sentence_id, array $textitems)
    {
        $this->SeID = $sentence_id;
        $this->textitems = $textitems;
    }

    public function renderable() {
        return $this->textitems;
    }

    /** Static "factory" ... may belong in its own class, good enough here for now. */

    public static function getSentences(Text $text, TermRepository $repo)
    {
        if ($text->getID() == null)
            return [];

        $tokens = RenderableSentence::getTextTokens($text);
        if (count($tokens) == 0) {
            $text->getBook()->fullParse();
            $tokens = RenderableSentence::getTextTokens($text);
        }

        $terms = $repo->findTermsInText($text);

        $makeRenderableSentence = function($sentenceNum, $tokens, $terms, $text) {
            $setokens = array_filter($tokens, fn($t) => $t->TokSentenceNumber == $sentenceNum);
            $renderable = RenderableCalculator::getRenderable($terms, $setokens);
            $textitems = array_map(
                fn($i) => $i->makeTextItem($sentenceNum, $text->getID(), $text->getLanguage()->getLgID()),
                $renderable
            );
            return new RenderableSentence($sentenceNum, $textitems);
        };

        $usenums = array_map(fn($t) => $t->TokSentenceNumber, $tokens);
        $renderableSentences = array_map(
            fn($senum) => $makeRenderableSentence($senum, $tokens, $terms, $text),
            array_unique($usenums)
        );

        return $renderableSentences;
    }

    private static function getTextTokens(Text $t): array {
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

        $conn = Connection::getFromEnvironment();
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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

}
