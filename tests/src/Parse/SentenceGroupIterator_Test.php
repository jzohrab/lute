<?php declare(strict_types=1);

use App\Parse\SentenceGroupIterator;
use App\Parse\SpaceDelimitedParser;
use App\Entity\Language;
use PHPUnit\Framework\TestCase;

final class SentenceGroupIterator_Test extends TestCase
{

    private function toks_to_string($tokens) {
        $a = array_map(fn($t) => $t->token, $tokens);
        return implode('', $a);
    }

    private function scenario($s, $maxcount, $expected_groups) {
        $eng = Language::makeEnglish();
        $parser = new SpaceDelimitedParser();
        $tokens = $parser->getParsedTokens($s, $eng);

        $it = new SentenceGroupIterator($tokens, $maxcount);
        $groups = [];
        while ($g = $it->next())
            $groups[] = $g;

        $gs = array_map(fn($g) => $this->toks_to_string($g), $groups);
        $this->assertEquals(
            implode("||", $gs),
            implode("||", $expected_groups),
            "groups for size $maxcount"
        );
    }

    public function test_group_all_in_one_group() {  // V3-port: TODO
        $this->scenario("", 100, [ "" ]);

        $text = "Here is a dog. Here is a cat.";

        $this->scenario(
            $text, 100,
            [ "Here is a dog. Here is a cat." ]);

        $this->scenario(
            $text, 3,
            [ "Here is a dog. ", "Here is a cat." ]);

        $this->scenario(
            $text, 6,
            [ "Here is a dog. ", "Here is a cat." ]);

        $this->scenario( // No period at end.
            "Here is a dog. Here is a cat", 6,
            [ "Here is a dog. ", "Here is a cat" ]);

        $this->scenario( // No period at all.
            "Here is a dog Here is a cat", 6,
            [ "Here is a dog Here is a cat" ]);

        $this->scenario(
            "Here is a dog. Here is a cat. Here is a thing.",
            10,
            [
                "Here is a dog. Here is a cat. ",
                "Here is a thing."
            ]);

    }
}
