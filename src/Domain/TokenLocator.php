<?php

namespace App\Domain;

/** Helper class for finding tokens and positions in arrays of
 * tokens. */
class TokenLocator {

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

    private static function get_count_before($string, $pos, $zws): int {
        $beforesubstr = mb_substr($string, 0, $pos, 'UTF-8');
        // echo "     get count, string = {$string} \n";
        // echo "     get count, pos = {$pos} \n";
        // echo "     get count, before = {$beforesubstr} \n";
        if ($beforesubstr == '')
            return 0;
        $parts = explode($zws, $beforesubstr);
        $parts = array_filter($parts, fn($s) => $s != '');
        // echo "     get count, parts:\n ";
        // dump($parts) . "\n";
        $n = count($parts);
        // echo "     get count, result = {$n} \n";
        return $n;
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
    public static function locate($subject, $find_patt) {
        $zws = mb_chr(0x200B);
        $len_zws = mb_strlen($zws);
        $wordlen = mb_strlen($find_patt) - 2 * $len_zws;

        $LCsubject = mb_strtolower($subject);
        $LCpatt = mb_strtolower($find_patt);
        $pos = mb_strpos($LCsubject, $LCpatt, 0);

        $termmatches = [];
        while ($pos !== false) {
            $rtext = mb_substr($subject, $pos + $len_zws, $wordlen);
            $i = TokenLocator::get_count_before($subject, $pos, $zws);
            $termmatches[] = [ $rtext, $i ];
            $pos = mb_strpos($LCsubject, $LCpatt, $pos + 1);
        }

        return $termmatches;
    }

}
