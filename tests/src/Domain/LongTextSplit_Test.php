<?php declare(strict_types=1);

use App\Domain\LongTextSplit;
use App\Domain\SpaceDelimitedParser;
use App\Entity\Language;
use PHPUnit\Framework\TestCase;

final class LongTextSplit_Test extends TestCase
{

    public function test_getSentences_no_token_ok() {
        $sentences = LongTextSplit::getSentences([]);
        $this->assertEquals([], $sentences);
    }

    private function toks_to_string($tokens) {
        $a = array_map(fn($t) => $t->token, $tokens);
        return implode('', $a);
    }
    
    public function test_one_sentence_returned() {
        $eng = Language::makeEnglish();
        $parser = new SpaceDelimitedParser();
        $tokens = $parser->getParsedTokens("Here is a dog.", $eng);
        $sentences = LongTextSplit::getSentences($tokens);
        $this->assertEquals(1, count($sentences), 'one sentence');
        $s = $this->toks_to_string($sentences[0]);
        $this->assertEquals("Here is a dog.", $s);
    }

    public function test_two_sentence_returned() {
        $eng = Language::makeEnglish();
        $parser = new SpaceDelimitedParser();
        $tokens = $parser->getParsedTokens("Here is a dog. Here is a cat.", $eng);
        $sentences = LongTextSplit::getSentences($tokens);
        $this->assertEquals(2, count($sentences), '2');
        $s = $this->toks_to_string($sentences[0]);
        $this->assertEquals("Here is a dog. ", $s);
        $s = $this->toks_to_string($sentences[1]);
        $this->assertEquals("Here is a cat.", $s);
    }

    private function scenario($s, $maxcount, $expected_groups) {
        $eng = Language::makeEnglish();
        $parser = new SpaceDelimitedParser();
        $tokens = $parser->getParsedTokens($s, $eng);

        $groups = LongTextSplit::groups($tokens, $maxcount);

        $gs = array_map(fn($g) => $this->toks_to_string($g), $groups);
        $this->assertEquals(
            implode("||", $gs),
            implode("||", $expected_groups),
            "groups for size $maxcount"
        );
    }

    public function test_group_all_in_one_group() {
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

        $this->scenario(
            "Here is a dog. Here is a cat. Here is a thing.",
            10,
            [
                "Here is a dog. Here is a cat. ",
                "Here is a thing."
            ]);

    }
}
