<?php

namespace App\Domain;

use App\Entity\Language;

/** Helper class for finding tokens and positions in arrays of
 * tokens. */
class TokenLocator {

    private Language $language;
    private string $subject;
    private float $pregmatchtime = 0;

    public function __construct($language, $subject) {
        $this->language = $language;
        $this->subject = $subject;
    }

    /**
     * Finds a given token (word) in a sentence (an array of tokens),
     * ignoring case, returning the actual word in the sentence (its
     * original case), and its index.
     *
     * For example, given:
     *   - $subject "/this/ /CAT/ /is/ /big/"
     *   - $find_patt = "/cat/"
     * (where "/" is the zero-width space to indicate word boundaries)
     * this method would return [ "CAT", 2 ]
     *   - the token "cat" is actually "CAT" (uppercase) in the sentence
     *   - it's at index = 2
     *
     * See the test cases for more examples.
     */
    public function locateString($tlc) {
        $find_patt = TokenLocator::make_string($tlc);
        $LCpatt = $this->language->getLowercase($find_patt);

        // "(?=())" is required because sometimes the search pattern can
        // overlap -- e.g. _b_b_ has _b_ *twice*.
        // Ref https://stackoverflow.com/questions/22454032/
        //   preg-match-all-how-to-get-all-combinations-even-overlapping-ones
        $pattern = '/(?=(' . preg_quote($LCpatt) . '))/ui';
        $timenow = microtime(true);

        $subjLC = $this->language->getLowercase($this->subject);
        $matches = $this->pregMatchCapture($pattern, $subjLC);
        $this->pregmatchtime += (microtime(true) - $timenow);
        // dump($matches);

        // The matches were actually with the lowercased string,
        // because some languages (Turkish!) have funny cases ...
        // so we need to convert the matched text back to the _original_
        // subject string.
        $subj = $this->subject;
        // dump('here is the subject:');
        // dump($subj);
        $makeTextIndexPair = function($match) use ($subj) {
            $matchtext = $match[0];  // includes zws at start and end.
            $matchlen = mb_strlen($matchtext);
            $matchpos = $match[1];

            $original_subject_text = mb_substr($subj, $matchpos, $matchlen);
            $zws = mb_chr(0x200B);
            $t = mb_ereg_replace("(^{$zws}+)|({$zws}+$)", '', $original_subject_text);
            $index = $this->get_count_before($this->subject, $matchpos);
            return [ $t, $index ];
        };

        $termmatches = array_map($makeTextIndexPair, $matches);

        return $termmatches;
    }

    private function get_count_before($string, $pos): int {
        $zws = mb_chr(0x200B);
        $beforesubstr = mb_substr($string, 0, $pos, 'UTF-8');
        $n = mb_substr_count($beforesubstr, $zws);
        return $n;
    }

    /**
     * Returns array of matches in same format as preg_match or
     * preg_match_all
     * @param string $pattern  The pattern to search for, as a string.
     * @param string $subject  The input string.
     * @return array
     *
     * Ref https://stackoverflow.com/questions/1725227/preg-match-and-utf-8-in-php
     */
    private function pregMatchCapture($pattern, $subject)
    {
        $matchInfo = array();
        $n = preg_match_all($pattern, $subject, $matchInfo, PREG_OFFSET_CAPTURE, 0);
        if ($n == 0 || empty($matchInfo))
            return [];

        $result = [];
        $matches = $matchInfo[1];  // Use 1 for the lookahead
        foreach ($matches as $match) {
            $matchedText   = $match[0];
            $matchedLength = $match[1];
            $result[]   = [
                $matchedText,
                mb_strlen(mb_strcut($subject, 0, $matchedLength))
            ];
        }

        return $result;
    }

    // public function debugPrintStats() {
    //    // dump('TokenLocator preg match time: ' . $this->pregmatchtime);
    // }
    
    /**
     * Create a search string, adding zero-width spaces between each
     * token as the word boundary (to simplify string matching).
     */
    public static function make_string($t) {
        $zws = mb_chr(0x200B);
        if (is_array($t))
            $t = implode($zws, $t);
        return $zws . $t . $zws;
    }

    public static function locate(Language $lang, $subject, $needle) {
        $tocloc = new TokenLocator($lang, $subject);
        return $tocloc->locateString($needle);
    }

}
