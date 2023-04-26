<?php

namespace App\Domain;

class RenderableCalculator {

    private function get_count_before($string, $pos, $zws): int {
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
        $len_zws = mb_strlen($zws);
        $toktext = array_map(fn($t) => $t->TokText, $texttokens);
        $subject = $zws . implode($zws, $toktext) . $zws;
        $LCsubject = mb_strtolower($subject);

        $print = function($name, $s) use ($zws) {
            // echo $name . ': ' . str_replace($zws, '_', $s) . "\n";
        };

        $print('lc subject', $LCsubject);

        foreach ($words as $w) {
            $tlc = $w->getTextLC();
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

                $rtext = mb_substr($subject, $pos + $len_zws, mb_strlen($tlc));
                $result->text = $rtext;

                $result->pos = $firstTokOrder + $this->get_count_before($subject, $pos, $zws);
                $result->length = $wtokencount;
                $result->isword = 1;
                $termmatches[] = $result;

                $print('  ->text', $result->text);
                $print('  ->pos', $result->pos);

                // Find the next instance.
                $pos = mb_strpos($LCsubject, $find_patt, $pos + 1);
            }
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
