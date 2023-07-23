<?php

namespace App\Domain;

/**
 * Calculating what TextTokens and Terms should be rendered.
 *
* Suppose we had the following TextTokens A-I, with spaces between:
*
*  A B C D E F G H I
*
* Then suppose we had the following Terms:
*   "B C"       (term J)
*   "E F G H I" (K)
*   "F G"       (L)
*   "C D E"     (M)
*
* Stacking these:
*
*  A B C D E F G H I
*
*   "B C"              (J)
*         "E F G H I"  (K)
*           "F G"      (L)
*     "C D E"          (M)
*
* We can say:
*
* - term J "contains" TextTokens B and C, so tokens B and C should not be rendered.
* - K contains tokens E-I, and also term L, so none of those should be rendered.
* - M is _not_ contained by anything else, so it should be rendered.
*/
class RenderableCalculator {

    private function assert_texttokens_are_contiguous($texttokens) {
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
    }

    private function get_all_RenderableCandidates($words, $texttokens) {

        // Tokens must be contiguous and in order!
        $cmp = function($a, $b) {
            if ($a->TokOrder != $b->TokOrder) {
                return ($a->TokOrder > $b->TokOrder) ? 1 : -1;
            }
        };
        usort($texttokens, $cmp);
        $this->assert_texttokens_are_contiguous($texttokens);

        $firstTokOrder = $texttokens[0]->TokOrder;
        $toktext = array_map(fn($t) => $t->TokText, $texttokens);
        $subject = TokenLocator::make_string($toktext);

        $termmatches = [];
        foreach ($words as $w) {
            $tlc = $w->getTextLC();
            $wtokencount = $w->getTokenCount();

            $find_patt = TokenLocator::make_string($tlc);
            $locations = TokenLocator::locate($subject, $find_patt);

            foreach ($locations as $loc) {
                $matchtext = $loc[0];
                $index = $loc[1];
                $result = new RenderableCandidate();
                $result->term = $w;
                $result->displaytext = $matchtext;
                $result->text = $matchtext;
                $result->pos = $firstTokOrder + $index;
                $result->length = $wtokencount;
                $result->isword = 1;
                $termmatches[] = $result;
            }
        }
        
        // Add originals
        foreach ($texttokens as $tok) {
            $result = new RenderableCandidate();
            $result->term = null;
            $result->displaytext = $tok->TokText;
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


    private function calc_overlaps(&$items) {
        for ($i = 1; $i < count($items); $i++) {
            // dump('---');
            $prev = $items[$i - 1];
            $prevlast = $prev->pos + $prev->length - 1;
            // dump("prev {$prev->text}; pos = {$prev->pos}, length = {$prev->length}, last = {$prevlast}");
            $curr = $items[$i];
            $currlast = $curr->pos + $curr->length - 1;
            // dump("curr {$curr->text}; pos = {$curr->pos}, length = {$curr->length}, last = {$currlast}");
            if ($prevlast >= $curr->pos) {
                $overlap = $prevlast - $curr->pos + 1;
                // dump("prev overlaps curr by {$overlap} tokens");
                $zws = mb_chr(0x200B);
                $curr_tokens = explode($zws, $curr->text);
                $show = array_slice($curr_tokens, $overlap);
                $show = implode($zws, $show);
                // dump('SHOULD ONLY SHOW ' . $show);
                $curr->displaytext = $show;
            }
        }
        // dump($items);
        return $items;
    }

    public function main($words, $texttokens) {
        $candidates = $this->get_all_RenderableCandidates($words, $texttokens);
        $candidates = $this->calculate_hides($candidates);
        $renderable = array_filter($candidates, fn($i) => $i->render);
        $items = $this->sort_by_order_and_tokencount($renderable);
        $items = $this->calc_overlaps($items);
        return $items;
    }

    public static function getRenderable($words, $texttokens) {
        $rc = new RenderableCalculator();
        return $rc->main($words, $texttokens);
    }

}
