<?php

namespace App\Domain;

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
}