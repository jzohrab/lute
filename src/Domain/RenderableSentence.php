<?php

namespace App\Domain;

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

}
