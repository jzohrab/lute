<?php

namespace tests\App\Domain;
 
use App\Domain\RenderableCalculator;
use App\Domain\SpaceDelimitedParser;
use App\DTO\TextToken;
use App\Entity\Term;
use App\Entity\Language;
use PHPUnit\Framework\TestCase;
 
class RenderableCalculator_Test extends TestCase
{

    private function makeTokens($token_data) {
        $makeToken = function($arr) {
            $t = new TextToken();
            $t->TokOrder = $arr[0];
            $t->TokText = $arr[1];
            $t->TokTextLC = strtolower($arr[1]);
            $t->TokIsWord = 1;
            return $t;
        };
        return array_map(fn($t) => $makeToken($t), $token_data);
    }

    private function assertRenderableEquals($token_data, $word_data, $expected, $expecteddisplayed = null) {
        $tokens = $this->makeTokens($token_data);

        $makeTerm = function($arr) {
            $eng = Language::makeEnglish();
            $w = new Term($eng, $arr[0]);
            return $w;
        };
        $words = array_map(fn($t) => $makeTerm($t), $word_data);

        $rc = new RenderableCalculator();
        $p = new SpaceDelimitedParser();
        $rcs = $rc->main($p, $words, $tokens);
        $res = '';
        foreach ($rcs as $rc) {
            if ($rc->render) {
                $res .= "[{$rc->text}-{$rc->length}]";
            }
        }

        $zws = mb_chr(0x200B);
        $res = str_replace($zws, '', $res);
        $this->assertEquals($res, $expected, 'rendered');

        if ($expecteddisplayed != null) {
            $res = '';
            foreach ($rcs as $rc) {
                if ($rc->render) {
                    $res .= "[{$rc->displaytext}-{$rc->length}]";
                }
            }

            $zws = mb_chr(0x200B);
            $res = str_replace($zws, '', $res);
            $this->assertEquals($res, $expecteddisplayed, 'displayed text');
        }
    }


    public function test_simple_render()
    {
        $data = [
            [ 1, 'some' ],
            [ 2, ' ' ],
            [ 3, 'data' ],
            [ 4, ' ' ],
            [ 5, 'here' ],
            [ 6, '.' ]
        ];
        $expected = '[some-1][ -1][data-1][ -1][here-1][.-1]';
        $this->assertRenderableEquals($data, [], $expected);
    }

    // Just in case, since ordering is so important.
    public function test_data_out_of_order_still_ok()
    {
        $data = [
            [ 1, 'some' ],
            [ 5, 'here' ],
            [ 4, ' ' ],
            [ 3, 'data' ],
            [ 2, ' ' ],
            [ 6, '.' ]
        ];
        $expected = '[some-1][ -1][data-1][ -1][here-1][.-1]';
        $this->assertRenderableEquals($data, [], $expected);
    }

    // Just in case, since contiguity is so important.
    public function test_tokens_must_be_contiguous()
    {
        $data = [
            [ 1, 'some' ],
            [ 5, 'here' ],
            [ 4, ' ' ],
            [ 3, 'data' ],
            [ 2, ' ' ],
            [ 7, '.' ]
        ];
        $tokens = $this->makeTokens($data);

        $rc = new RenderableCalculator();
        $this->expectException(\Exception::class);
        $rcs = $rc->main(new SpaceDelimitedParser(), [], $tokens);
    }

    public function test_multiword_items_cover_other_items()
    {
        $data = [
            [ 1, 'some' ],
            [ 5, 'here' ],
            [ 4, ' ' ],
            [ 3, 'data' ],
            [ 2, ' ' ],
            [ 6, '.' ]
        ];
        $words = [
            [ 'data here' ]
        ];

        $expected = "[some-1][ -1][data here-3][.-1]";
        $this->assertRenderableEquals($data, $words, $expected);
    }

    public function test_overlapping_multiwords()
    {
        $data = [
            [ 1, 'some' ],
            [ 2, ' ' ],
            [ 3, 'data' ],
            [ 4, ' ' ],
            [ 5, 'here' ],
            [ 6, '.' ]
        ];
        $words = [
            [ 'some data' ],
            [ 'data here' ]
        ];

        $expected = "[some data-3][data here-3][.-1]";
        $expecteddisplayed = "[some data-3][ here-3][.-1]";
        $this->assertRenderableEquals($data, $words, $expected, $expecteddisplayed);
    }

    /**
     * @group rccurr
     */
    public function test_multiwords_starting_at_same_location()
    {
        $chars = str_split('A B C D');
        $data = [];
        foreach ($chars as $c) {
            $data[] = [count($data) + 1, $c];
        };
        $words = [
            [ 'A B' ],
            [ 'A B C' ]
        ];

        $expected = "[A B C-5][ -1][D-1]";
        $this->assertRenderableEquals($data, $words, $expected);
    }


    /* Test case directly from the class documentation. */
    public function test_crazy_case()
    {
        $chars = str_split('A B C D E F G H I');
        $data = [];
        foreach ($chars as $c) {
            $data[] = [count($data) + 1, $c];
        };
        $words = [
            [ 'B C' ], // J
            [ 'E F G H I' ],  // K
            [ 'F G' ],  // L
            [ 'C D E' ] // M
        ];
        $expected =          '[A-1][ -1][B C-3][C D E-5][E F G H I-9]';
        $expecteddisplayed = '[A-1][ -1][B C-3][ D E-5][ F G H I-9]';
        $this->assertRenderableEquals($data, $words, $expected, $expecteddisplayed);
    }

}