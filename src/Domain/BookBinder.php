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
        int $maxWordTokensPerText = 250
    )
    {
        $p = $lang->getParser();
        $tokens = $p->getParsedTokens($fulltext, $lang);
        $it = new SentenceGroupIterator($tokens, $maxWordTokensPerText);

        $tokstring = function($tokens) {
            $a = array_map(fn($t) => $t->token, $tokens);
            $ret = implode('', $a);
            $ret = str_replace("\r", '', $ret);
            $ret = str_replace("¶", "\n", $ret);
            return trim($ret);
        };

        $b = new Book();
        $b->setLanguage($lang);
        $b->setTitle($title);

        $count = $it->count();
        $i = 0;
        while ($toks = $it->next()) {
            $i++;
            $txt = $tokstring($toks);

            $t = new Text();
            $t->setLanguage($lang);
            $t->setOrder($i);
            $t->setText($txt);

            $b->addText($t);
        }

        return $b;
    }

}
