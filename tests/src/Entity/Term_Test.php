<?php

namespace tests\App\Entity;
 
use App\Entity\Term;
use App\Entity\Language;
use App\Parse\JapaneseParser;
use PHPUnit\Framework\TestCase;
 
class Term_Test extends TestCase
{

    public function test_cruft_stripped_on_setWord()  // V3-port: TODO
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

    public function test_TokenCount()  // V3-port: TODO
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
            $this->assertEquals($t->getTokenCount(), $c[2], '*' . $c[0] . '*');
        }
    }

    /**
     * @group tokencount
     */
    public function test_TokenCount_punct()  // V3-port: TODO
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
            $this->assertEquals($t->getTokenCount(), $c[2], $m . ' tc');
        }
    }

    /**
     * @group tokencount
     */
    public function test_TokenCount_japanese()  // V3-port: TODO
    {
        if (!JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $cases = [ "私", "元気", "です" ];
        $jp = Language::makeJapanese();

        foreach ($cases as $c) {
            $t = new Term($jp, $c);
            $this->assertEquals($t->getTokenCount(), 1, 'token count');
            $this->assertEquals($t->getText(), $c, 'text');
            $this->assertEquals($t->getTextLC(), $c, 'lc');
        }

        $cases = [
            [ "元気です", 2 ],
            [ "元気です私", 3 ]
        ];

        foreach ($cases as $c) {
            $t = new Term($jp, $c[0]);
            $this->assertEquals($t->getTokenCount(), $c[1], "token count for " . $c[0]);
        }

    }

    /**
     * @group tokencountexception
     */
    public function test_term_left_as_is_if_its_an_exception()  // V3-port: TODO
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

    /**
     * @group noselfparent
     */
    public function test_cannot_add_self_as_own_parent() {  // V3-port: TODO
        $sp = Language::makeSpanish();
        $t = new Term($sp, 'gato');
        $t->addParent($t);
        $this->assertEquals(count($t->getParents()), 0, 'no parents');
    }

    /**
     * @group downcasing
     */
    public function test_downcasing_handled_correctly() {  // V3-port: TODO

        $sp = Language::makeSpanish();
        $en = Language::makeEnglish();
        $tu = Language::makeTurkish();
        $cases = [
            [ $sp, 'GATO', 'gato' ],
            [ $sp, 'gato', 'gato' ],

            [ $en, 'GATO', 'gato' ],
            [ $en, 'gato', 'gato' ],

            # cases from https://github.com/jzohrab/lute/issues/71
            [ $tu, 'İÇİN', 'için' ],
            [ $tu, 'IŞIK', 'ışık' ],
            [ $tu, 'İçin', 'için' ],
            [ $tu, 'Işık', 'ışık' ],
        ];

        foreach ($cases as $case) {
            $lang = $case[0];
            $t = new Term($lang, $case[1]);
            $this->assertEquals($t->getTextLC(), $case[2], "{$lang->getLgName()}, {$case[1]}");
        }

        if (JapaneseParser::MeCab_installed()) {
            $lang = Language::makeJapanese();
            $t = new Term($lang, '元気');
            $this->assertEquals($t->getTextLC(), '元気', "jp");
        }
    }

}