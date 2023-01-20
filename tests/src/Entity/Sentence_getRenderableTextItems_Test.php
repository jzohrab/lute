<?php

namespace tests\App\Entity;
 
use App\Entity\Sentence;
use App\Entity\TextItem;
use PHPUnit\Framework\TestCase;
 
class Sentence_getRenderableTextItems_Test extends TestCase
{

    private Sentence $sentence;

    // $data = [ [ order, text, tokencount ], ... ]
    private function make_sentence($data)
    {
        $makeTextItem = function($row) {
            $t = new TextItem();
            $t->Order = $row[0];
            $t->Text = $row[1];
            $t->TokenCount = $row[2];
            return $t;
        };
        $textItems = array_map($makeTextItem, $data);

        $s = new Sentence(1, $textItems);
        $this->sentence = $s;
        return $s;
    }


    private function assertRenderableEquals($data, $expected) {
        $sentence = $this->make_sentence($data);
        $actual = '';
        foreach ($sentence->renderable() as $ti)
            $actual .= "[{$ti->Text}-{$ti->TokenCount}]";
        $this->assertEquals($actual, $expected);
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
        $this->assertRenderableEquals($data, $expected);
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
        $this->assertRenderableEquals($data, $expected);
    }

    public function test_multiword_items_cover_other_items()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 5, 'here', 1 ],
            [ 4, ' ', 1 ],
            [ 3, 'data', 1 ],
            [ 2, ' ', 1 ],
            [ 3, 'data here', 3 ],  // <<<
            [ 6, '.', 1 ]
        ];
        $expected = '[some-1][ -1][data here-3][.-1]';
        $this->assertRenderableEquals($data, $expected);
    }

    public function test_multiword_textitem_indicates_which_items_it_covers()
    {
        $data = [
            [ 1, 'some', 1 ],
            [ 5, 'here', 1 ],
            [ 4, ' ', 1 ],
            [ 3, 'data', 1 ],
            [ 2, ' ', 1 ],
            [ 3, 'data here', 3 ],  // <<<
            [ 6, '.', 1 ]
        ];
        $expected = '[some-1][ -1][data here-3][.-1]';
        $this->assertRenderableEquals($data, $expected);

        $textitems = $this->sentence->getTextItems();
        $this->assertEquals(count($textitems), 7, "all text items returned");
        $mwords = array_filter($textitems, fn($t) => ($t->Text == 'data here'));
        $mword = array_values($mwords)[0];

        $this->assertEquals($mword->Text, 'data here', 'sanity check, ensure got the entry back');
        $hides = array_map(fn($t) => $t->Order . '_' . $t->Text, $mword->hides);
        sort($hides);
        $this->assertEquals(implode(', ', $hides), '3_data, 4_ , 5_here');
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
        $data = [
            [ 1, 'A', 1 ],
            [ 2, 'B', 1 ],
            [ 3, 'C', 1 ],
            [ 4, 'D', 1 ],
            [ 5, 'E', 1 ],
            [ 6, 'F', 1 ],
            [ 7, 'G', 1 ],
            [ 8, 'H', 1 ],
            [ 9, 'I', 1 ],
            [ 2, 'J', 2 ],
            [ 5, 'K', 5 ],
            [ 6, 'L', 2 ],
            [ 3, 'M', 3 ]
        ];
        $expected = '[A-1][J-2][M-3][K-5]';
        $this->assertRenderableEquals($data, $expected);
    }

}