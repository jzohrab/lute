<?php

namespace App\Domain;

use App\Entity\Language;

abstract class AbstractParser {

    abstract public function getParsedTokens(string $text, Language $lang);

}