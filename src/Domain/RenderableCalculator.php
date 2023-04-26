<?php

namespace App\Domain;

class RenderableCalculator {

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
    private function pregMatchCapture($pattern, $subject, $offset = 0)
    {
        if ($offset != 0) { $offset = strlen(mb_substr($subject, 0, $offset)); }
        
        $matchInfo = array();
        $flag = PREG_OFFSET_CAPTURE;

        $printable = function($s) { return str_replace(mb_chr(0x200B), '_', $s); };
        echo '<pre>' . var_export([$method, $printable($pattern), $printable($subject), $matchInfo, $flag, $offset]) . '</pre>';
        // $subject = str_replace(mb_chr(0x200B), '', $subject);
        // $pattern = str_replace(mb_chr(0x200B), '', $pattern);
        $n = preg_match_all($pattern, $subject, $matchInfo, PREG_OFFSET_CAPTURE, $offset);

        $result = array();
        if ($n !== 0 && !empty($matchInfo)) {
            echo '<p>Match info:</p>';
            echo '<pre>' . var_export($matchInfo) . '</pre>';
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
        }
        return $result;
    }


    private function get_count_before($string, $pos, $zws): int {
        $beforesubstr = mb_substr($string, 0, $pos, 'UTF-8');
        echo "     get count, string = {$string} \n";
        echo "     get count, pos = {$pos} \n";
        echo "     get count, before = {$beforesubstr} \n";
        if ($beforesubstr == '')
            return 0;
        $parts = explode($zws, $beforesubstr);
        $parts = array_filter($parts, fn($s) => $s != '');
        echo "     get count, parts:\n ";
        echo var_dump($parts) . "\n";
        $n = count($parts);
        echo "     get count, result = {$n} \n";
        return $n;
    }

    private function get_all_textitems($words, $texttokens) {
        $termmatches = [];

        // Tokens must be contiguous and in order!
        $cmp = function($a, $b) {
            if ($a->TokOrder != $b->TokOrder) {
                return ($a->TokOrder > $b->TokOrder) ? 1 : -1;
            }
        };
        usort($texttokens, $cmp);
        $prevtok = null;
        foreach($texttokens as $tok) {
            if ($prevtok != null) {
                if ($prevtok->TokOrder != ($tok->TokOrder - 1)) {
                    $mparts = [
                        $prevtok->TokText, $prevtok->TokOrder,
                        $tok->TokText, $tok->TokOrder
                    ];
                    $msg = implode('; ', $mparts);
                    throw new \Exception("bad token ordering: {$msg}");
                }
            }
            $prevtok = $tok;
        }

        $firstTokOrder = $texttokens[0]->TokOrder;
        $zws = '__zws__'; // mb_chr(0x200B)
        $len_zws = mb_strlen($zws);
        $toktext = array_map(fn($t) => $t->TokText, $texttokens);
        $subject = $zws . implode($zws, $toktext) . $zws;
        $LCsubject = mb_strtolower($subject);

        $print = function($name, $s) use ($zws) {
            echo $name . ': ' . str_replace($zws, '_', $s) . "\n";
        };

        echo "\n-------------\n";
        $print('lc subject', $LCsubject);

        foreach ($words as $w) {
            // $pattern = '/' . $zws . '('. preg_quote($w->getTextLC()) . ')' . $zws . '/ui';
            // $allmatches = $this->pregMatchCapture($pattern, $subject, 0);

            $tlc = str_replace(mb_chr(0x200B), $zws, $w->getTextLC());
            $wtokencount = count(explode($zws, $tlc));
            $find_patt = $zws . $tlc . $zws;
            $patt_len = mb_strlen($find_patt);
            $pos = mb_strpos($LCsubject, $find_patt, 0);

            $print('  tlc', $tlc);
            $print('  find_patt', $find_patt);
            $print('  patt_len', $patt_len);

            while ($pos !== false) {
                $result = new RenderableCandidate();
                $result->term = $w;

                $fullmatch = mb_substr($subject, $pos, $patt_len);
                $rtext = mb_substr($fullmatch, $len_zws, mb_strlen($tlc));
                $rtext = str_replace($zws, mb_chr(0x200B), $rtext);
                $result->text = $rtext;

                // 1 is subtracted because the sentence has an extra $zws at the start,
                // so there is always an empty element at the start of the sentence.
                $result->pos = $firstTokOrder + $this->get_count_before($subject, $pos, $zws);
                $result->length = $wtokencount;
                $result->isword = 1;
                // echo "------------\n";
                $termmatches[] = $result;

                $print('  ->text', $result->text);
                $print('  ->pos', $result->pos);

                // Find the next instance.
                $pos = mb_strpos($LCsubject, $find_patt, $pos + 1);
            }

            /*
            $printable = function($s) { return str_replace(mb_chr(0x200B), '_', $s); };
            echo "<p>checking word: {$w->getText()}</p>";
            echo "<p>pattern: {$printable($pattern)}</p>";
            echo "<p>subject: {$printable($subject)}</p>";
            echo '<pre>' . var_export($allmatches, true) . '</pre>';

            if (count($allmatches) > 0) {
                // echo "in loop\n";
                // echo "===============\n";
                // var_dump($allmatches);
                // var_dump($allmatches[0]);
                // echo "===============\n";
                foreach ($allmatches[1] as $m) {
                    // echo "------------\n";
                    // var_dump($m);
                    $result = new RenderableCandidate();
                    $result->term = $w;
                    $result->text = $m[0];

                    // 1 is subtracted because the sentence has an extra $zws at the start,
                    // so there is always an empty element at the start of the sentence.
                    $result->pos = $firstTokOrder + $this->get_count_before($subject, $m[1]) - 1;
                    $result->length = count(explode($zws, $w->getTextLC()));
                    $result->isword = 1;
                    // echo "------------\n";
                    $termmatches[] = $result;
                }
            }
            // else {
            // echo "no match for pattern $pattern \n";
            // }
            */
        }
        
        // Add originals
        foreach ($texttokens as $tok) {
            $result = new RenderableCandidate();
            $result->term = null;
            $result->text = $tok->TokText;
            $result->pos = $tok->TokOrder;
            $result->length = 1;  // Each thing parsed is 1 token!
            $result->isword = $tok->TokIsWord;
            $termmatches[] = $result;
        }
        return $termmatches;
    }

    private function calculate_hides(&$items) {
        $isTerm = function($i) { return $i->term != null; };
        $checkwords = array_filter($items, $isTerm);
        // echo "checking words ----------\n";
        // var_dump($checkwords);
        // echo "------\n";
        foreach ($checkwords as &$mw) {
            $isContained = function($i) use ($mw) {
                $contained = ($i->pos >= $mw->pos) && ($i->OrderEnd() <= $mw->OrderEnd());
                $equivalent = ($i->pos == $mw->pos) && ($i->OrderEnd() == $mw->OrderEnd()) && ($i->getTermID() == $mw->getTermID());
                return $contained && !$equivalent;
            };
            $hides = array_filter($items, $isContained);
            // echo "checkword {$mw->text} has hides:\n";
            // var_dump($hides);
            // echo "end hides\n";
            $mw->hides = $hides;
            foreach ($hides as &$hidden) {
                // echo "hiding " . $hidden->text . "\n";
                $hidden->render = false;
            }
        }
        return $items;
    }


    private function sort_by_order_and_tokencount($items): array
    {
        $cmp = function($a, $b) {
            if ($a->pos != $b->pos) {
                return ($a->pos > $b->pos) ? 1 : -1;
            }
            // Fallback: descending order, by token count.
            return ($a->length > $b->length) ? -1 : 1;
        };
        usort($items, $cmp);
        return $items;
    }


    public function main($words, $texttokens) {
        $candidates = $this->get_all_textitems($words, $texttokens);
        $candidates = $this->calculate_hides($candidates);
        $renderable = array_filter($candidates, fn($i) => $i->render);
        $items = $this->sort_by_order_and_tokencount($renderable);
        return $items;
    }
}
