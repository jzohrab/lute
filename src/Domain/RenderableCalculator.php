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


    private function get_count_before($string, $pos): int {
        $beforesubstr = mb_substr($string, 0, $pos - 1, 'UTF-8');
        $zws = mb_chr(0x200B);
        $parts = explode($zws, $beforesubstr);
        return count($parts);
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
        $zws = mb_chr(0x200B);
        $toktext = array_map(fn($t) => $t->TokText, $texttokens);
        $subject = $zws . implode($zws, $toktext) . $zws;

        foreach ($words as $w) {
            $pattern = '/' . $zws . '('. preg_quote($w->getTextLC()) . ')' . $zws . '/ui';
            $allmatches = $this->pregMatchCapture(true, $pattern, $subject, 0);
            
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
