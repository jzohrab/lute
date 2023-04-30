<?php

namespace App\Domain;

/**
 * Split long texts into sensible amounts, breaking on sentences and
 * token count to keep page sizes reasonable.
 */
class LongTextSplit
{

    public static function getSentences($tokens): array {
        if (count($tokens) == 0)
            return [];

        $sentences = [];
        $currsent = [];
        foreach ($tokens as $tok) {
            $currsent[] = $tok;
            if ($tok->isEndOfSentence) {
                $sentences[] = $currsent;
                $currsent = [];
            }
        }

        // Sometimes sentences don't have a \r at the end,
        // so add whatever is left.
        if (count($currsent) > 0)
            $sentences[] = $currsent;

        return $sentences;
    }

    public static function groups($tokens, $maxWordCount = 500) {
        $sentences = LongTextSplit::getSentences($tokens);

        $groups = [];
        $currgroup = [];
        $currgroupwordcount = 0;

        $word_count = function($sent) {
            $words = array_filter($sent, fn($t) => ($t->isWord == 1));
            return count($words);
        };

        foreach ($sentences as $sent) {
            $swcount = $word_count($sent);
            if ($currgroupwordcount + $swcount <= $maxWordCount) {
                $currgroup[] = $sent;
                $currgroupwordcount += $swcount;
            }
            else {
                // Overflow if the sentence is appended, so
                // add the current group if it has any content.
                if ($currgroupwordcount != 0)
                    $groups[] = array_merge([], ...$currgroup);
                $currgroup = [ $sent ];
                $currgroupwordcount = $swcount;
            }
        }

        // Add anything leftover.
        if (count($currgroup) > 0)
            $groups[] = array_merge([], ...$currgroup);

        return $groups;
    }
}