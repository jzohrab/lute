<?php

namespace App\Render;

use App\Entity\Text;
use App\Utils\Connection;
use App\DTO\TextToken;
use App\Domain\TermService;
use App\Parse\ParsedToken;

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

        $language = $text->getBook()->getLanguage();
        $tokens = RenderableSentence::getTextTokens($text);
        if (count($tokens) == 0) {
            $text->getBook()->fullParse();
            $tokens = RenderableSentence::getTextTokens($text);
        }
        $tokens = array_filter($tokens, fn($t) => $t->TokText != '¶');
        $terms = $svc->findAllInString($text->getText(), $text->getLanguage());

        $makeRenderableSentence = function($pnum, $sentenceNum, $tokens, $terms, $text) use ($language) {
            $setokens = array_filter($tokens, fn($t) => $t->TokSentenceNumber == $sentenceNum);
            $renderable = RenderableCalculator::getRenderable($language, $terms, $setokens);
            $textitems = array_map(
                fn($i) => $i->makeTextItem($pnum, $sentenceNum, $text->getID(), $language),
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

        // dump($renderableParas);
        return $renderableParas;
    }

    private static function getTextTokens(Text $t): array {
        $textid = $t->getID();
        if ($textid == null)
            return [];
        $txt = $t->getText();

        // Replace double spaces, because they can mess up multi-word
        // terms (e.g., "llevar[ ][ ]a" is different from "llevar[
        // ]a").  Note this is duplicated code from ParsedTokenSaver
        // ... therefore is bad.  Should be extracted somewhere.
        // TODO:remove_duplicate_logic
        $txt = preg_replace('/ +/u', ' ', $txt);

        $pts = $t->getLanguage()->getParsedTokens($txt);
        return ParsedToken::createTextTokens($pts);
    }

}
