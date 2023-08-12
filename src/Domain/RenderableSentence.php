<?php

namespace App\Domain;

use App\Entity\Text;
use App\Utils\Connection;
use App\DTO\TextToken;
use App\Domain\TermService;

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

    /**
     * Get arrays of arrays of TextItems to be rendered.
     * e.g.
     * [
     *   [ renderable sentence 1, sentence 2, etc ]  // first paragraph
     * ]
     */
    public static function getParagraphs(Text $text, TermService $svc)
    {
        if ($text->getID() == null)
            return [];

        $tokens = RenderableSentence::getTextTokens($text);
        if (count($tokens) == 0) {
            $text->getBook()->fullParse();
            $tokens = RenderableSentence::getTextTokens($text);
        }
        $tokens = array_filter($tokens, fn($t) => $t->TokText != '¶');
        $terms = $svc->findAllInString($text->getText(), $text->getLanguage());

        $makeRenderableSentence = function($pnum, $sentenceNum, $tokens, $terms, $text) {
            $setokens = array_filter($tokens, fn($t) => $t->TokSentenceNumber == $sentenceNum);
            $renderable = RenderableCalculator::getRenderable($terms, $setokens);
            $textitems = array_map(
                fn($i) => $i->makeTextItem($pnum, $sentenceNum, $text->getID(), $text->getLanguage()->getLgID()),
                $renderable
            );
            return new RenderableSentence($sentenceNum, $textitems);
        };

        $paranums = array_map(fn($t) => $t->TokParagraphNumber, $tokens);
        $paranums = array_unique($paranums);
        $renderableParas = [];
        foreach ($paranums as $pnum) {
            $paratokens = array_filter($tokens, fn($t) => $t->TokParagraphNumber == $pnum);
            $senums = array_map(fn($t) => $t->TokSentenceNumber, $paratokens);
            $senums = array_unique($senums);
            $renderableParas[] = array_map(
                fn($senum) => $makeRenderableSentence($pnum, $senum, $paratokens, $terms, $text),
                $senums
            );
        }
        return $renderableParas;
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
        $currpara = 1;
        foreach ($rows as $row) {
            $tok = new TextToken();
            $tok->TokSentenceNumber = intval($row['TokSentenceNumber']);
            $tok->TokParagraphNumber = $currpara;
            $tok->TokOrder = intval($row['TokOrder']);
            $tok->TokIsWord = intval($row['TokIsWord']);
            $tok->TokText = $row['TokText'];
            $tok->TokTextLC = $row['TokTextLC'];

            if ($tok->TokText == '¶')
                $currpara += 1;

            $ret[] = $tok;
        }
        return $ret;
    }

}
