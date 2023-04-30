<?php

namespace tests\App\Domain;
 
use App\Domain\RenderableCalculator;
use App\DTO\TextToken;
use App\Entity\Term;
use App\Entity\Language;
use PHPUnit\Framework\TestCase;
 
class RenderableCalculator_Test extends TestCase
{

    private function assertRenderableEquals($token_data, $word_data, $expected) {
        $makeToken = function($arr) {
            $t = new TextToken();
            $t->TokOrder = $arr[0];
            $t->TokText = $arr[1];
            $t->TokTextLC = strtolower($arr[1]);
            $t->TokIsWord = 1;
            return $t;
        };
        $tokens = array_map(fn($t) => $makeToken($t), $token_data);

        $makeTerm = function($arr) {
            $eng = Language::makeEnglish();
            $w = new Term($eng, $arr[0]);
            return $w;
        };
        $words = array_map(fn($t) => $makeTerm($t), $word_data);

        $rc = new RenderableCalculator();
        $rcs = $rc->main($words, $tokens);
        $res = '';
        foreach ($rcs as $rc) {
            if ($rc->render) {
                $res .= "[{$rc->text}-{$rc->length}]";
            }
        }

        $zws = mb_chr(0x200B);
        $res = str_replace($zws, '', $res);
        $this->assertEquals($res, $expected);
    }


    public function test_simple_render()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 2, ' ', 1 ],
            [ 3, 'data', 1 ],
            [ 4, ' ', 1 ],
            [ 5, 'here', 1 ],
            [ 6, '.', 1 ]
        ];
        $expected = '[some-1][ -1][data-1][ -1][here-1][.-1]';
        $this->assertRenderableEquals($data, [], $expected);
    }

    // Just in case, since ordering is so important.
    public function test_data_out_of_order_still_ok()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 5, 'here', 1 ],
            [ 4, ' ', 1 ],
            [ 3, 'data', 1 ],
            [ 2, ' ', 1 ],
            [ 6, '.', 1 ]
        ];
        $expected = '[some-1][ -1][data-1][ -1][here-1][.-1]';
        $this->assertRenderableEquals($data, [], $expected);
    }

    public function test_multiword_items_cover_other_items()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 5, 'here', 1 ],
            [ 4, ' ', 1 ],
            [ 3, 'data', 1 ],
            [ 2, ' ', 1 ],
            [ 6, '.', 1 ]
        ];
        $words = [
            [ 'data here', 3 ]
        ];

        $expected = "[some-1][ -1][data here-3][.-1]";
        $this->assertRenderableEquals($data, $words, $expected);
    }


    /* From the class documentation:
     *
     * Graphically, suppose we had the following text items, where A-I are
     * WordCount 0 or WordCount 1, and J-M are multiwords:
     *
     *  A   B   C   D   E   F   G   H   I
     *    |---J---|   |---------K---------|
     *                    |---L---|
     *        |-----M---|
     *
     * J contains B and C, so B and C should not be rendered.
     * 
     * K contains E-I and also L, so none of those should be rendered.
     *
     * M is _not_ contained by anything else, so it should be rendered.
     */
    public function test_crazy_case()
    {
        $chars = str_split('A B C D E F G H I');
        $data = [];
        foreach ($chars as $c) {
            $data[] = [count($data) + 1, $c];
        };
        $words = [
            [ 'B C', 2 ], // J
            [ 'E F G H I', 5 ],  // K
            [ 'F G', 2 ],  // L
            [ 'C D E', 3 ] // M
        ];
        $expected = '[A-1][ -1][B C-3][C D E-5][E F G H I-9]';
        $this->assertRenderableEquals($data, $words, $expected);
    }

}