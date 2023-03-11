<?php

namespace tests\App\Domain;

use App\Domain\BookBinder;
use App\Entity\Book;
use App\Entity\Language;
use PHPUnit\Framework\TestCase;
 
class BookBinder_Test extends TestCase
{

    public function test_create_book_creates_texts()
    {
        $eng = Language::makeEnglish();
        $b = BookBinder::makeBook("title", $eng, "Here is a dog. And a cat.", 5);

        $texts = $b->getTexts();
        $this->assertEquals(count($texts), 2, "2 texts");
        $this->assertEquals($texts[0]->getText(), "Here is a dog.");
        $this->assertEquals($texts[1]->getText(), "And a cat.");
    }

}