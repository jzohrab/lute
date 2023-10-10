<?php

namespace tests\App\Entity;

use DateTime;
use App\Entity\Text;
use App\Entity\Language;
use PHPUnit\Framework\TestCase;
 
class Text_Test extends TestCase
{

    /**
     * @group textsentences
     *
     * Sentences should only be generated when a Text is saved with the ReadDate saved.
     * Sentences are only used for reference lookups, 
     */
    public function test_sentence_lifecycle()
    {
        $eng = Language::makeEnglish();
        $t = new Text();
        $t->setLanguage($eng);
        $t->setText("Tienes un perro. Un gato.");

        $this->assertEquals(count($t->getSentences()), 0, 'no sentences');

        $t->setReadDate(new DateTime("now"));
        $this->assertEquals(count($t->getSentences()), 2, 'have on read');

        $fn = function($s) {
            $zws = mb_chr(0x200B); // zero-width space.
            return str_replace($zws, '/', $s->getSeText());
        };
        $this->assertEquals($fn($t->getSentences()[0]), '/Tienes/ /un/ /perro/./', '1st sentence');
        $this->assertEquals($fn($t->getSentences()[1]), '/Un/ /gato/./', '2');

        $t->setText("Tengo un coche.");
        $this->assertEquals(count($t->getSentences()), 1, 'changed');
        $this->assertEquals($fn($t->getSentences()[0]), '/Tengo/ /un/ /coche/./', 'changed');
    }

}