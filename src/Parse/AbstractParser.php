<?php

namespace App\Parse;

use App\Entity\Language;

abstract class AbstractParser {

    abstract public function getParsedTokens(string $text, Language $lang);

    /**
     * Many writing systems are phonetic and do not need a phonetic reading.
     */
    public function getReading(string $text) {
        return null;
    }

    /**
     * Many writing systems can just downcase, but some (Turkish!) have special quirks.
     */
    public function getLowercase(string $text) {
        return mb_strtolower($text);
    }

}