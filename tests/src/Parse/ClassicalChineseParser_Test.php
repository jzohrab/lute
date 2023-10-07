<?php declare(strict_types=1);


use App\Domain\ClassicalChineseParser;
use App\Domain\ParsedToken;
use App\Entity\Language;
use PHPUnit\Framework\TestCase;

final class ClassicalChineseParser_Test extends TestCase
{

    private Language $lang;
    private ClassicalChineseParser $parser;

    public function setUp(): void
    {
        $this->lang = Language::makeClassicalChinese();
        $this->parser = new ClassicalChineseParser();
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
    
    public function test_sample_1() {
        $text = "學而時習之，不亦說乎？";
        $tokens = $this->parser->getParsedTokens($text, $this->lang);

        $expected = [
            [ '學', true ],
            [ '而', true ],
            [ '時', true ],
            [ '習', true ],
            [ '之', true ],
            [ '，', false ],
            [ '不', true ],
            [ '亦', true ],
            [ '說', true ],
            [ '乎', true ],
            [ "？", false, true ]
        ];

        $this->assertTokensEquals($tokens, $expected);
    }

    public function test_sample_2() {
        $text = "學而時習之，不亦說乎？
有朋自遠方來，不亦樂乎？";
        $tokens = $this->parser->getParsedTokens($text, $this->lang);

        $expected = [
            [ '學', true ],
            [ '而', true ],
            [ '時', true ],
            [ '習', true ],
            [ '之', true ],
            [ '，', false ],
            [ '不', true ],
            [ '亦', true ],
            [ '說', true ],
            [ '乎', true ],
            [ "？", false, true ],
            [ "¶", false, true ],
            [ "有", true ],
            [ "朋", true ],
            [ "自", true ],
            [ "遠", true ],
            [ "方", true ],
            [ "來", true ],
            [ "，", false ],
            [ "不", true ],
            [ "亦", true ],
            [ "樂", true ],
            [ "乎", true ],
            [ "？", false, true ],
        ];

        $this->assertTokensEquals($tokens, $expected);
    }
}