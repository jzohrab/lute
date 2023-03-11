<?php

namespace App\Domain;

use App\Entity\Language;
use App\Entity\Book;
use App\Entity\Text;

class BookBinder {

    /**
     * Makes a book, splitting the text as needed.
     */
    public static function makeBook(
        string $title,
        Language $lang,
        string $fulltext,
        int $maxWordTokensPerText = 500
    )
    {
        $p = $lang->getParser();
        $tokens = $p->getParsedTokens($fulltext, $lang);
        $groups = LongTextSplit::groups($tokens, $maxWordTokensPerText);

        $tokstring = function($tokens) {
            $a = array_map(fn($t) => $t->token, $tokens);
            return trim(str_replace("\r", '', implode('', $a)));
        };
        $textstrings = array_map(fn($g) => $tokstring($g), $groups);

        $b = new Book();
        $b->setLanguage($lang);
        $b->setTitle($title);

        $count = count($textstrings);
        for ($i = 1; $i <= $count; $i++) {
            $t = new Text();
            $t->setLanguage($lang);
            $t->setTitle("{$title} ({$i}/{$count})");
            $t->setOrder($i);
            $t->setText($textstrings[$i - 1]);

            $b->addText($t);
        }

        return $b;
    }

}
