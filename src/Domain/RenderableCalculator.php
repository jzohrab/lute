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

    private function get_all_textitems($words, $texttokens) {

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
