<?php

namespace App\Domain;

use App\Entity\Language;

abstract class AbstractParser {

    abstract public function getParsedTokens(string $text, Language $lang);

    /**
     * Many writing systems are phonetic and do not need a phonetic reading.
     */
    public function getReading(string $text) {
        return null;
    }
}