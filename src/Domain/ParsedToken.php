<?php

namespace App\Domain;

class ParsedToken
{
    public string $token;
    public bool $isWord;

    public function __construct(string $token, bool $isWord) {
        $this->token = $token;
        $this->isWord = $isWord;
    }
}