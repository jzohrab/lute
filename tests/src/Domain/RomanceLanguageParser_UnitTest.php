<?php declare(strict_types=1);

use App\Domain\RomanceLanguageParser;
use App\Domain\ParsedToken;
use App\Entity\Text;
use App\Entity\Language;
use App\Entity\Term;
use PHPUnit\Framework\TestCase;

final class RomanceLanguageParser_UnitTest extends TestCase
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

    /**
     * @group parser_eos
     */
    public function test_end_of_sentence_stored_in_parsed_tokens()
    {
        $p = new RomanceLanguageParser();
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
            [ '.', false, false ]  // note false.
        ];

        $this->assertTokensEquals($actual, $expected);
    }


    public function test_exceptions_are_considered_when_splitting_sentences()
    {
        $p = new RomanceLanguageParser();
        $s = "Mrs. Jones is here.";
        $e = Language::makeEnglish();
        $actual = $p->getParsedTokens($s, $e);

        $expected = [
            [ 'Mrs.', true, false ],
            [ ' ', false ],
            [ 'Jones', true ],
            [ ' ', false ],
            [ 'is', true ],
            [ ' ', false ],
            [ 'here', true ],
            [ ".", false, false ]
        ];

        $this->assertTokensEquals($actual, $expected);
    }

}
