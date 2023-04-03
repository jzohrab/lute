<?php

namespace tests\App\Entity;
 
use App\Entity\Term;
use App\Entity\Language;
use App\Domain\JapaneseParser;
use PHPUnit\Framework\TestCase;
 
class Term_Test extends TestCase
{

    public function test_cruft_stripped_on_setWord()
    {
        $cases = [
            [ 'hola', 'hola', 'hola' ],
            [ '    hola    ', 'hola', 'hola' ],

            // This case should never occur:
            // tabs are stripped out of text, and returns mark different sentences.
            // [ "   hola\tGATO\nok", 'hola GATO ok', 'hola gato ok' ],
        ];

        $spanish = Language::makeSpanish();
        foreach ($cases as $c) {
            $t = new Term($spanish, $c[0]);
            $this->assertEquals($t->getText(), $c[1]);
            $this->assertEquals($t->getTextLC(), $c[2]);
        }
    }

    public function test_getWordCount_and_TokenCount()
    {
        $cases = [
            [ "hola", 1, 1 ],
            [ "    hola    ", 1, 1 ],
            [ "  hola 	gato", 2, 3 ],
            [ "HOLA hay\tgato  ", 3, 5 ]
        ];

        $english = new Language();
        $english->setLgName('English');  // Use defaults for all settings.

        foreach ($cases as $c) {
            $t = new Term($english, $c[0]);
            $this->assertEquals($t->getWordCount(), $c[1], '*' . $c[0] . '*');
            $this->assertEquals($t->getTokenCount(), $c[2], '*' . $c[0] . '*');

            // If wc is set, it's used.
            $t->setWordCount(17);
            $this->assertEquals($t->getWordCount(), 17, 'override');
        }
    }

    /**
     * @group wordcount
     */
    public function test_getWordCount_and_TokenCount_punct()
    {
        $cases = [
            [ "  the CAT's pyjamas  ", 4, 7 ],

            // This only has 9 tokens, because the "'" is included with
            // the following space ("' ").
            [ "A big CHUNK O' stuff", 5, 9 ],
            [ "YOU'RE", 2, 3 ],
            [ "...", 0, 1 ]  // should never happen :-)
        ];

        $english = new Language();
        $english->setLgName('English');  // Use defaults for all settings.

        foreach ($cases as $c) {
            $t = new Term($english, $c[0]);
            $m = $c[0];
            $this->assertEquals($t->getWordCount(), $c[1], $m . ' wc');
            $this->assertEquals($t->getTokenCount(), $c[2], $m . ' tc');
        }
    }

    /**
     * @group wordcount
     */
    public function test_getWordCount_and_TokenCount_japanese()
    {
        if (!JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $cases = [ "私", "元気", "です" ];
        $jp = Language::makeJapanese();

        foreach ($cases as $c) {
            $t = new Term($jp, $c);
            $this->assertEquals($t->getWordCount(), 1, 'count got ' . $t->getWordCount());
            $this->assertEquals($t->getTokenCount(), 1, 'token count got ' . $t->getWordCount());
            $this->assertEquals($t->getText(), $c, 'text');
            $this->assertEquals($t->getTextLC(), $c, 'lc');
        }

        $cases = [
            [ "元気です", 2 ],
            [ "元気です私", 3 ]
        ];

        foreach ($cases as $c) {
            $t = new Term($jp, $c[0]);
            $this->assertEquals($t->getWordCount(), $c[1], "word count for " . $c[0]);
            $this->assertEquals($t->getTokenCount(), $c[1], "token count for " . $c[0]);
        }

    }

    /**
     * @group wordcountexception
     */
    public function test_term_left_as_is_if_its_an_exception()
    {
        $sp = Language::makeSpanish();
        $sp->setLgExceptionsSplitSentences("EE.UU.");

        $t = new Term($sp, 'EE.UU.');
        $this->assertEquals($t->getTokenCount(), 1, "1 token");
        $this->assertEquals($t->getText(), 'EE.UU.');

        $t = new Term($sp, 'ee.uu.');
        $this->assertEquals($t->getTokenCount(), 1, "1 token");
        $this->assertEquals($t->getText(), 'ee.uu.');
    }

}