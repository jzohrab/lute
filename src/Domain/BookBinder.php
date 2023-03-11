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
        int $maxTokensPerText = 500
    )
    {
        return new Book();
    }

}
