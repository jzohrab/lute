<?php

namespace App\Domain;

use App\DTO\TextToken;

class ParsedToken
{
    public string $token;
    public bool $isWord;
    public bool $isEndOfSentence;

    public function __construct(string $token, bool $isWord, bool $isEOS = false) {
        $this->token = $token;
        $this->isWord = $isWord;
        $this->isEndOfSentence = $isEOS;
        // $this->isEndOfSentence = str_ends_with($token, "\r");
    }

    /**
     * Convert array of ParsedTokens to array of TextTokens.
     */
    public static function createTextTokens($parsedTokens) {
        $ret = [];

        $sentenceNumber = 0;
        $paraNumber = 0;
        $tokOrder = 0;

        foreach ($parsedTokens as $pt) {
            $tok = new TextToken();
            $tok->TokText = $pt->token;
            $tok->TokIsWord = $pt->isWord;

            $tok->TokOrder = $tokOrder;
            $tok->TokSentenceNumber = $sentenceNumber;
            $tok->TokParagraphNumber = $paraNumber;

            // Increment counters /after/ the TexToken has been
            // completed, so that it belongs to the correct
            // sentence/paragraph.
            $tokOrder += 1;
            if ($pt->isEndOfSentence)
                $sentenceNumber += 1;
            if ($pt->token == '¶')
                $paraNumber += 1;

            $ret[] = $tok;
        }

        return $ret;
    }

}