<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Repository\TextItemRepository;
use App\Domain\TextStatsCache;
use App\Utils\Connection;

abstract class AbstractParser {

    abstract public function getParsedTokens(string $text, Language $lang);

}