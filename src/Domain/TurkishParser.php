<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Domain\ParsedToken;
use App\Utils\Connection;

class TurkishParser extends SpaceDelimitedParser {

    /**
     * Handle Ii challenge
     */
    public function getLowercase(string $text) {
        $find = array('İ','I');
        $replace = array('i', 'ı');
        $str = str_replace($find, $replace, $text);
        return mb_strtolower($str);
    }

}