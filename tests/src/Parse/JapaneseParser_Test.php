<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Parse\JapaneseParser;
use App\Parse\ParsedToken;
use App\Entity\Text;
use App\Entity\Language;
use App\Entity\Term;

final class JapaneseParser_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        if (!JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = Language::makeJapanese();
        $this->language_repo->save($japanese, true);
        $this->japanese = $japanese;
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_parse_no_words_defined()  // V3-port: DONE skip, already covered
    {
        $t = $this->make_text("Test", "私は元気です.", $this->japanese);
        $this->assert_rendered_text_equals($t, "私/は/元気/です/.");
    }

    /**
     * @group jp_sentence_ends
     */
    public function test_getParsedTokens_sentence_ends()  // V3-port: DONE test_japaneseParser
    {
        $p = new JapaneseParser();
        // First, western punctuation, then JP :-)
        $s = "元気.元気?元気!
元気。元気？元気！";
        $actual = $p->getParsedTokens($s, $this->japanese);

        $expected = [
            [ "元気", true ],
            [ ".", false, true ],
            [ "元気", true ],
            [ "?", false, true ],
            [ "元気", true ],
            [ "!", false, true ],
            [ "¶", false, true ],
            [ "元気", true ],
            [ "。", false, true ],
            [ "元気", true ],
            [ "？", false, true ],
            [ "元気", true ],
            [ "！", false, true ],
            [ "¶", false, true ]
        ];
        $expected = array_map(fn($a) => new ParsedToken(...$a), $expected);

        $tostring = function($tokens) {
            $ret = '';
            foreach ($tokens as $tok) {
                $isw = $tok->isWord ? '1' : '0';
                $iseos = $tok->isEndOfSentence ? '1' : '0';
                $ret .= "{$tok->token}-{$isw}-{$iseos};";
            }
            return $ret;
        };

        $this->assertEquals($tostring($actual), $tostring($expected));
    }

    /**
     * @group jp_parser_tokens
     */
    public function test_getParsedTokens()  // V3-port: DONE skipping
    {
        $p = new JapaneseParser();
        $s = "私は元気です。
私は元気です。";
        $actual = $p->getParsedTokens($s, $this->japanese);

        $expected = [
            [ "私", true ],
            [ "は", true ],
            [ "元気", true ],
            [ "です", true ],
            [ "。", false, true ],
            [ "¶", false, true ],
            [ "私", true ],
            [ "は", true ],
            [ "元気", true ],
            [ "です", true ],
            [ "。", false, true ],
            [ "¶", false, true ]
        ];
        $expected = array_map(fn($a) => new ParsedToken(...$a), $expected);

        $tostring = function($tokens) {
            $ret = '';
            foreach ($tokens as $tok) {
                $isw = $tok->isWord ? '1' : '0';
                $iseos = $tok->isEndOfSentence ? '1' : '0';
                $ret .= "{$tok->token}-{$isw}-{$iseos};";
            }
            return $ret;
        };

        $this->assertEquals($tostring($actual), $tostring($expected));
    }

    /**
     * @group jpeos
     */
    public function test_parse_words_defined()  // V3-port: DONE feature
    {
        $this->addTerms($this->japanese, [ '私', '元気', 'です' ]);
        $t = $this->make_text("Test", "私は元気です.", $this->japanese);
        $this->assert_rendered_text_equals($t, "私(1)/は/元気(1)/です(1)/.");
    }

    // futari wasn't getting parsed correctly.
    /**
     * @group futari
     */
    public function test_futari()  // V3-port: DONE skipping
    {
        $t = $this->make_text("Test", "二人はどちらの力が強いか.", $this->japanese);
        $this->assert_rendered_text_equals($t, "二/人/は/どちら/の/力/が/強い/か/.");
    }


    /**
     * @group reading
     */
    public function test_reading()  // V3-port: DONE test_japaneseParser
    {
        $p = new JapaneseParser();

        // Don't bother giving reading for a few cases:
        $noreading = [
            'NHK', // roman
            'ツヨイ', // only katakana
            'どちら' // only hiragana
        ];
        foreach ($noreading as $c) {
            $this->assertEquals(null, $p->getReading($c), $c);
        }

        $zws = mb_chr(0x200B);
        $cases = [
            [ '強い', 'ツヨイ' ],
            [ '二人', 'ニニン' ], // ah well, not perfect :-)
            [ "強い{$zws}か", "ツヨイ{$zws}カ" ],  // zero-width-space ignored
        ];
        foreach ($cases as $c) {
            $this->assertEquals($c[1], $p->getReading($c[0]), $c[0]);
        }
    }

    // Tests to do:

    // carriage returns handled correctly.  e.g:
    // 私は元気です.
    // 彼は元気です.
    // should both be loaded, and there should be a record with carriage returns, eg
    // "x; y; ¶; ¶",

    // terms already defined in "words" table

    // multi-word terms defined.

}
