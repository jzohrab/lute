<?php

namespace tests\App\Domain;

use App\Domain\TokenLocator;
use PHPUnit\Framework\TestCase;

class TokenLocator_Test extends TestCase
{

    public function test_scenario()
    {
        $zws = mb_chr(0x200B);

        // These cases are a bit hard to read ...
        // an explanation:
        //
        // For each case,
        // The first line (e.g. [ "a", "b", "c", "d" ]) is the list of tokens.
        // The second is the word to find.
        // Third is the expected result.
        $cases = [
            // Finds b
            [ [ "a", "b", "c", "d" ],
              "b",
              [ [ "b", 1 ] ]
            ],

            // Case doesn't matter
            [ [ "A", "B", "C", "D" ],
              "b",
              [ [ "B", 1 ] ]  // The original case is returned
            ],

            [ [ "a", "b", "c", "d" ],
              "B",
              [ [ "b", 1 ] ]  // Original case returned.
            ],

            [ [ "a", "bb", "c", "d" ],
              "B",
              []  // No match
            ],

            [ [ "b", "b", "c", "d" ],
              "b",
              [ [ "b", 0 ], [ "b", 1 ] ]  // Found in multiple places.
            ],

            [ [ "b", "B", "b", "d" ],
              "b{$zws}b",
              [ [ "b{$zws}B", 0 ], [ "B{$zws}b", 1 ] ]  // multiword, found in multiple
            ],

            [ [ "b", "B", "c", "b", "b", "x", "b" ],
              "b{$zws}b",
              [ [ "b{$zws}B", 0 ], [ "b{$zws}b", 3 ] ]  // multiword, found in multiple
            ],

            [ [ "a", " ", "cat", " ", "here" ],
              "cat",
              [ [ "cat", 2 ] ]
            ],

            [ [ "a", " ", "CAT", " ", "here" ],
              "cat",
              [ [ "CAT", 2 ] ]
            ],

            [ [ "a", " ", "CAT", " ", "here" ],
              "ca",
              []  // no match
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