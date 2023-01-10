<?php

namespace App\DTO;

class TermReferenceDTO
{
    public int $TxID;
    public string $Title;
    public ?string $Sentence;

    public function __construct(int $txid, string $title, ?string $s) {
        $this->TxID = $txid;
        $this->Title = $title;
        $this->Sentence = $s;
    }
}