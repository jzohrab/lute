<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Domain\ParsedToken;
use App\Utils\Connection;

class SpaceDelimitedParser extends AbstractParser {

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

    /**
     * Returns array of matches in same format as preg_match or
     * preg_match_all
     * @param bool   $matchAll If true, execute preg_match_all, otherwise preg_match
     * @param string $pattern  The pattern to search for, as a string.
     * @param string $subject  The input string.
     * @param int    $offset   The place from which to start the search (in bytes).
     * @return array
     *
     * Ref https://stackoverflow.com/questions/1725227/preg-match-and-utf-8-in-php
     */
    private function pregMatchCapture($matchAll, $pattern, $subject, $offset = 0)
    {
        if ($offset != 0) { $offset = strlen(mb_substr($subject, 0, $offset)); }

        $matchInfo = array();
        $method    = 'preg_match';
        $flag      = PREG_OFFSET_CAPTURE;
        if ($matchAll) {
            $method .= '_all';
        }

        // var_dump([$method, $pattern, $subject, $matchInfo, $flag, $offset]);
        $n = $method($pattern, $subject, $matchInfo, $flag, $offset);

        $result = array();
        if ($n !== 0 && !empty($matchInfo)) {
            if (!$matchAll) {
                $matchInfo = array($matchInfo);
            }
            foreach ($matchInfo as $matches) {
                $positions = array();
                foreach ($matches as $match) {
                    $matchedText   = $match[0];
                    $matchedLength = $match[1];
                    // dump($subject);
                    $positions[]   = array(
                        $matchedText,
                        mb_strlen(mb_strcut($subject, 0, $matchedLength))
                    );
                }
                $result[] = $positions;
            }
            if (!$matchAll) {
                $result = $result[0];
            }
        }
        return $result;
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

        $text = $this->do_replacements($text, [
            [ "\r\n",  "\n" ],
            [ '{', '['],
            [ '}', ']']
        ]);

        $tokens = [];
        foreach (explode("\n", $text) as $para) {
            $this->parse_para($para, $lang, $tokens);
            $tokens[] = new ParsedToken('¶', false, true);
        }

        // Remove superfluous last para mark.
        return array_slice($tokens, 0, count($tokens) - 1);
    }

    private function parse_para(string $text, Language $lang, &$tokens) {

        $termchar = $lang->getLgRegexpWordCharacters();
        $splitSentence = preg_quote($lang->getLgRegexpSplitSentences());
        $splitex = str_replace('.', '\\.', $lang->getLgExceptionsSplitSentences());
        $m = $this->pregMatchCapture(true, "/($splitex|[$termchar]*)/ui", $text, 0);
        $wordtoks = array_filter($m[0], fn($t) => $t[0] != "");
        // dump($text);
        // dump($termchar . '    ' . $splitex);
        // dump($m);
        // dump($wordtoks);

        $addNonWords = function($s) use (&$tokens, $splitSentence) {
            if ($s == "")
                return;
            // dump("ADDING NON WORDS $s");
            $pattern = '/[' . $splitSentence . ']/ui';
            $allmatches = $this->pregMatchCapture(true, $pattern, $s, 0);
            $hasEOS = count($allmatches) > 0;
            $tokens[] = new ParsedToken($s, false, $hasEOS);
        };

        $pos = 0;
        foreach ($wordtoks as $wt) {
            // dump("handle token " . $wt[0]);
            $w = $wt[0];
            $wp = $wt[1];

            // stuff before
            $s = mb_substr($text, $pos, $wp - $pos);
            $addNonWords($s);

            // the word
            $tokens[] = new ParsedToken($w, true, false);

            $pos = $wp + mb_strlen($w);
        }
        // Get part after last, if any.
        $s = mb_substr($text, $pos);
        $addNonWords($s);
        // dump($tokens);

        return;
    }
}