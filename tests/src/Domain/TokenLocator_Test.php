<?php

namespace tests\App\Domain;

use App\Domain\TokenLocator;
use PHPUnit\Framework\TestCase;

class TokenLocator_Test extends TestCase
{

    public function test_create_book_creates_texts()
    {
        $tokens = [ "a", "b", "c", "d" ];
        $word = "b";

        $sentence = TokenLocator::make_string($tokens);
        $word = TokenLocator::make_string($word);

        $actual = TokenLocator::locate($sentence, $word);
        $expected = [ [ "b", 1 ] ];
        // dump($actual);
        // dump($expected);
        $this->assertEquals($actual, $expected);
    }

}