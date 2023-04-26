<?php

namespace tests\App\Domain;

use App\Domain\TokenLocator;
use PHPUnit\Framework\TestCase;

class TokenLocator_Test extends TestCase
{

    public function test_scenario()
    {
        $cases = [
            [ [ "a", "b", "c", "d" ],
              "b",
              [ [ "b", 1 ] ]
            ],
        ];

        foreach ($cases as $case) {
            $tokens = $case[0];
            $word = $case[1];
            $expected = $case[2];

            $sentence = TokenLocator::make_string($tokens);
            $word = TokenLocator::make_string($word);
            $actual = TokenLocator::locate($sentence, $word);
            // dump($actual);
            // dump($expected);

            $msg = implode('', $tokens) . ' == ' . $word;
            $this->assertEquals($actual, $expected, );
        }
    }

}