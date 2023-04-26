<?php

namespace App\Domain;

class TokenLocator {

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
        // echo var_dump($parts) . "\n";
        $n = count($parts);
        // echo "     get count, result = {$n} \n";
        return $n;
    }


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
