<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Domain\ParsedToken;
use App\Utils\Connection;

class ClassicalChineseParser extends AbstractParser {

    public function getParsedTokens(string $text, Language $lang) {
        return $this->parse_to_tokens($text, $lang);
    }

    // A (possibly) easier way to do substitutions -- each
    // pair in $replacements is run in order.
    // Possible entries:
    // ( <src string or regex string (starting with '/')>, <target (string or callback)> )
    private function do_replacements($text, $replacements) {
        foreach($replacements as $r) {
            if ($r == 'skip') {
                continue;
            }

            if ($r == 'trim') {
                $text = trim($text);
                continue;
            }

            $src = $r[0];
            $tgt = $r[1];

            // echo "=====================\n";
            // echo "Applying '$src' \n\n";

            if (! is_string($tgt)) {
                $text = preg_replace_callback($src, $tgt, $text);
            }
            else {
                if (substr($src, 0, 1) == '/')
                    $text = preg_replace($src, $tgt, $text);
                else
                    $text = str_replace($src, $tgt, $text);
            }

            // echo "text is ---------------------\n";
            // echo str_replace("\r", "<RET>\n", $text);
            // echo "\n-----------------------------\n";
        }
        return $text;
    }

    private function parse_to_tokens(string $text, Language $lang) {

        $replace = explode("|", $lang->getLgCharacterSubstitutions());
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $rfrom = trim($fromto[0]);
                $rto = trim($fromto[1]);
                $text = str_replace($rfrom, $rto, $text);
            }
        }

        $splitSentence = $lang->getLgRegexpSplitSentences();
        $termchar = $lang->getLgRegexpWordCharacters();

        $text = $this->do_replacements($text, [
            [ "\r\n",  "\n" ],
            [ '{',     '['],
            [ '}',     ']'],
            [ "\n",    '¶' ],
            [ '/\s+/u', '' ],
            'trim'
        ]);

        $chars = mb_str_split($text);
        $tokens = [];
        foreach($chars as $char) {
            $isword = (preg_match("/^[$termchar]$/u", $char) == 1);
            $isEndOfSentence = (preg_match("/^[$splitSentence]$/u", $char) == 1);
            if ($char == '¶')
                $isEndOfSentence = true;
            $tok = new ParsedToken($char, $isword, $isEndOfSentence);
            $tokens[] = $tok;
        }

        return $tokens;
    }

}