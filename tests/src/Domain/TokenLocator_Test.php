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
            [ 1,
              [ "a", "b", "c", "d" ],
              "b",
              [ [ "b", 1 ] ]
            ],

            // Case doesn't matter
            [ 2,
              [ "A", "B", "C", "D" ],
              "b",
              [ [ "B", 1 ] ]  // The original case is returned
            ],

            [ 3,
              [ "a", "b", "c", "d" ],
              "B",
              [ [ "b", 1 ] ]  // Original case returned.
            ],

            [ 4,
              [ "a", "bb", "c", "d" ],
              "B",
              []  // No match
            ],

            [ 5,
              [ "b", "b", "c", "d" ],
              "b",
              [ [ "b", 0 ], [ "b", 1 ] ]  // Found in multiple places.
            ],

            [ 6,
              [ "b", "B", "b", "d" ],
              "b{$zws}b",
              [ [ "b{$zws}B", 0 ], [ "B{$zws}b", 1 ] ]  // multiword, found in multiple
            ],

            [ 7,
              [ "b", "B", "c", "b", "b", "x", "b" ],
              "b{$zws}b",
              [ [ "b{$zws}B", 0 ], [ "b{$zws}b", 3 ] ]  // multiword, found in multiple
            ],

            [ 8,
              [ "a", " ", "cat", " ", "here" ],
              "cat",
              [ [ "cat", 2 ] ]
            ],

            [ 9,
              [ "a", " ", "CAT", " ", "here" ],
              "cat",
              [ [ "CAT", 2 ] ]
            ],

            [ 10,
              [ "a", " ", "CAT", " ", "here" ],
              "ca",
              []  // no match
            ],

            [ 11,
              [ "b", "b", "c", "d" ],
              "x",
              []  // no match
            ],

           
        ];

        foreach ($cases as $case) {
            $casenum = intval($case[0]);

            // if ($casenum < 5)
            //     continue;

            $tokens = $case[1];
            $word = $case[2];
            $expected = $case[3];

            $sentence = TokenLocator::make_string($tokens);
            $actual = TokenLocator::locate($sentence, $word);
            // dump($actual);
            // dump($expected);

            $msg = 'case ' . $casenum . '. ' . $sentence . ' , find: ' . $word;
            $msg = str_replace($zws, '/', $msg);
            $this->assertEquals($actual, $expected, $msg);
        }
    }

}