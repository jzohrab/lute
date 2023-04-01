<?php declare(strict_types=1);

use App\Domain\SpaceDelimitedParser;
use App\Domain\ParsedToken;
use App\Entity\Text;
use App\Entity\Language;
use App\Entity\Term;
use PHPUnit\Framework\TestCase;

final class SpaceDelimitedParser_Test extends TestCase
{

    private Language $spanish;

    public function setUp(): void
    {
        $this->spanish = Language::makeSpanish();
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    private function assertTokensEquals($actual, $expected) {
        $expected = array_map(fn($a) => new ParsedToken(...$a), $expected);

        $tostring = function($tokens) {
            $ret = '';
            foreach ($tokens as $tok) {
                $isw = $tok->isWord ? '1' : '0';
                $iseos = $tok->isEndOfSentence ? '1' : '0';
                $ret .= "{$tok->token}-{$isw}-{$iseos};\n";
            }
            return $ret;
        };

        $this->assertEquals($tostring($actual), $tostring($expected));
    }

    private function assert_tokens_equals(string $text, Language $lang, $expected)
    {
        $p = new SpaceDelimitedParser();
        $actual = $p->getParsedTokens($text, $lang);
        $this->assertTokensEquals($actual, $expected);
    }

    /**
     * @group parser_eos
     */
    public function test_end_of_sentence_stored_in_parsed_tokens()
    {
        $p = new SpaceDelimitedParser();
        $s = "Tengo un gato.\nTengo dos.";
        $actual = $p->getParsedTokens($s, $this->spanish);

        $expected = [
            [ 'Tengo', true ],
            [ ' ', false ],
            [ 'un', true ],
            [ ' ', false ],
            [ 'gato', true ],
            [ ".", false, true ],
            [ "¶", false, true ],
            [ 'Tengo', true ],
            [ ' ', false ],
            [ 'dos', true ],
            [ '.', false, true ]
        ];

        $this->assertTokensEquals($actual, $expected);
    }


    public function test_exceptions_are_considered_when_splitting_sentences()
    {
        $p = new SpaceDelimitedParser();
        $s = "1. Mrs. Jones is here.";
        $e = Language::makeEnglish();
        $actual = $p->getParsedTokens($s, $e);

        $expected = [
            [ '1. ', false, true ],
            [ 'Mrs.', true, false ],
            [ ' ', false ],
            [ 'Jones', true ],
            [ ' ', false ],
            [ 'is', true ],
            [ ' ', false ],
            [ 'here', true ],
            [ ".", false, true ]
        ];

        $this->assertTokensEquals($actual, $expected);
    }

    public function test_check_tokens()
    {
        $p = new SpaceDelimitedParser();
        $s = "1. Mrs. Jones is here.";
        $e = Language::makeEnglish();
        $actual = $p->getParsedTokens($s, $e);

        $expected = [
            [ '1. ',   false, true ],
            [ 'Mrs.',  true, false ],
            [ ' ',     false ],
            [ 'Jones', true ],
            [ ' ',     false ],
            [ 'is',    true ],
            [ ' ',     false ],
            [ 'here',  true ],
            [ ".",     false, true ]
        ];

        $this->assertTokensEquals($actual, $expected);
    }

    /**
     * @group reloadcurr1
     */
    public function test_single_que()
    {
        $text = "Tengo que y qué.";
        $expected = [
            [ 'Tengo',   true ],
            [ ' ',     false ],
            [ 'que', true ],
            [ ' ',     false ],
            [ 'y',    true ],
            [ ' ',     false ],
            [ 'qué',  true ],
            [ ".",     false, true ]
        ];
        $this->assert_tokens_equals($text, Language::makeSpanish(), $expected);
    }

    /**
     * @group eeuu
     */
    public function test_EE_UU_exception_should_be_considered()
    {
        $p = new SpaceDelimitedParser();
        $s = "Estamos en EE.UU. hola.";
        $sp = Language::makeSpanish();
        $sp->setLgExceptionsSplitSentences("EE.UU.");
        $actual = $p->getParsedTokens($s, $sp);

        $expected = [
            [ 'Estamos', true, false ],
            [ ' ', false ],
            [ 'en', true ],
            [ ' ', false ],
            [ 'EE.UU.', true ],
            [ ' ', false ],
            [ 'hola', true ],
            [ ".", false, true ]
        ];

        $this->assertTokensEquals($actual, $expected);
    }

    /**
     * @group eeuu
     */
    public function test_just_EE_UU()
    {
        $p = new SpaceDelimitedParser();
        $s = "EE.UU.";
        $sp = Language::makeSpanish();
        $sp->setLgExceptionsSplitSentences("EE.UU.");
        $actual = $p->getParsedTokens($s, $sp);

        $expected = [
            [ 'EE.UU.', true, false ],
        ];

        $this->assertTokensEquals($actual, $expected);
    }

    private function assert_string_equals(string $text, Language $lang, $expected)
    {
        $p = new SpaceDelimitedParser();
        $actual = $p->getParsedTokens($text, $lang);

        $tostring = function($tokens) {
            $ret = '';
            foreach ($tokens as $tok) {
                $s = $tok->token;
                if ($tok->isWord)
                    $s = '[' . $s . ']';
                $ret .= $s;
            }
            return $ret;
        };

        $this->assertEquals($tostring($actual), $expected);
    }

    public function test_quick_checks() {
        $e = Language::makeEnglish();
        $this->assert_string_equals("test", $e, "[test]");
        $this->assert_string_equals("test.", $e, "[test].");
        $this->assert_string_equals('"test."', $e, '"[test]."');
        $this->assert_string_equals('"test".', $e, '"[test]".');
        $this->assert_string_equals('Hi there.', $e, '[Hi] [there].');
        $this->assert_string_equals('Hi there.  Goodbye.', $e, '[Hi] [there].  [Goodbye].');
        $this->assert_string_equals("Hi.\nGoodbye.", $e, '[Hi].¶[Goodbye].');
        $this->assert_string_equals('He123llo.', $e, '[He]123[llo].');
        $this->assert_string_equals('1234', $e, '1234');
        $this->assert_string_equals('1234.', $e, '1234.');
        $this->assert_string_equals('1234.Hello', $e, '1234.[Hello]');
    }

    /*
- compare the demo stories parsed
- compare all existing texts?  or parse them in parallel for a while?
     */

}
