<?php

namespace App\Domain;

use App\Entity\Language;

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
*   "B C"              (term J)
*         "E F G H I"  (term K)
*           "F G"      (term L)
*     "C D E"          (term M)
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

    /**
     * Method to determine what should be rendered:
     *
     * 1. Create a "rendered array".  On completion of this algorithm,
     * each position in the array will be filled with the ID of the
     * RenderableCandidate that should actually appear there (and
     * which might hide other candidates).
     *     
     * 2. Start by saying that all the original texttokens will be
     * rendered by writing each candidate ID in the rendered array.
     *
     * 3. Create candidates for all the terms.
     * 
     * 4. Starting with the shortest terms first (fewest text tokens),
     * and starting _at the end_ of the string, "write" the candidate
     * ID to the output "rendered array", for each token in the candidate.
     *
     * At the end of this process, each position in the "rendered array"
     * should be filled with the ID of the corresponding candidate
     * that will actually appear in that position.  By getting the
     * unique IDs and returning just their candidates, we should have
     * the list of candidates that would be "visible" on render.
     *
     * Applying the above algorithm to the example given in the class
     * header:
     *     
     * We have the following TextTokens A-I, with spaces between:
     *
     *  a b c d e f g h i
     *
     * And the following terms, arranged from shortest to longest:
     *   "B C"
     *   "F G"
     *   "C D E"
     *   "E F G H I"
     *
     * First, terms are created for each individual token in the
     * original string:
     *
     * A B C D E F G H I
     *
     * Then the positions for each of the terms are calculated:
     *
     * [A B C D E F G H I]
     *
     *   "B C"
     *           "F G"
     *     "C D E"
     *         "E F G H I"
     *
     * Then, "writing" terms order by their length, and then by their
     * distance from the *end* of the string:
     *
     * - "F G" is written first, because it's short, and is nearest
     *   the end:
     *   => "A B C D E [F-G] H I"
     * - "B C" is next:
     *   => "A [B-C] D E [F-G] H I"
     * - then "C D E":
     *   => "A [B-C][C-D-E] [F-G] H I"
     * then "E F G H I":
     *   => "A [B-C][C-D-E][E-F-G-H-I]"
     */
    private function get_renderable($language, $terms, $texttokens) {

        // Pre-condition: contiguous ordered texttokens.
        $cmp = function($a, $b) {
            if ($a->TokOrder != $b->TokOrder) {
                return ($a->TokOrder > $b->TokOrder) ? 1 : -1;
            }
        };
        usort($texttokens, $cmp);
        $this->assert_texttokens_are_contiguous($texttokens);

        $candidateID = 0;
        $candidates = [];

        // Step 1.
        $rendered = [];

        // Step 2 - fill with the original texttokens.
        foreach ($texttokens as $tok) {
            $rc = new RenderableCandidate();
            $candidateID += 1;
            $rc->id = $candidateID;

            $rc->term = null;
            $rc->displaytext = $tok->TokText;
            $rc->text = $tok->TokText;
            $rc->pos = $tok->TokOrder;
            $rc->length = 1;  // Each thing parsed is 1 token!
            $rc->isword = $tok->TokIsWord;

            $candidates[$candidateID] = $rc;
            $rendered[$rc->pos] = $candidateID;
        }

        // 3.  Create candidates for all the terms.
        $termcandidates = [];
        $firstTokOrder = $texttokens[0]->TokOrder;
        $toktext = array_map(fn($t) => $t->TokText, $texttokens);
        $subject = TokenLocator::make_string($toktext);
        $tocloc = new TokenLocator($language, $subject);
        foreach ($terms as $term) {
            $tlc = $term->getTextLC();
            $wtokencount = $term->getTokenCount();
            $locations = $tocloc->locateString($tlc);
            
            foreach ($locations as $loc) {
                $rc = new RenderableCandidate();
                $candidateID += 1;
                $rc->id = $candidateID;

                $matchtext = $loc[0];
                $index = $loc[1];
                $rc->term = $term;
                $rc->displaytext = $matchtext;
                $rc->text = $matchtext;
                $rc->pos = $firstTokOrder + $index;
                $rc->length = $wtokencount;
                $rc->isword = 1;

                $termcandidates[] = $rc;
                $candidates[$candidateID] = $rc;
            }
        }
        // $tocloc->debugPrintStats();

        // 4a.  Sort the term candidates: first by length, then by position.
        $cmp = function($a, $b) {
            // Longest sorts first.
            if ($a->length != $b->length)
                return ($a->length > $b->length) ? -1 : 1;
            // Lowest position (closest to front of string) sorts first.
            return ($a->pos < $b->pos) ? -1 : 1;
        };
        usort($termcandidates, $cmp);

        // The $termcandidates should now be sorted such that longest
        // are first, with items of equal length being sorted by
        // position.  By traversing this in reverse and "writing"
        // their IDs to the "rendered" array, we should end up with
        // the final IDs in each position.
        foreach (array_reverse($termcandidates) as $tc) {
            for ($i = 0; $i < $tc->length; $i++)
                $rendered[$tc->pos + $i] = $tc->id;
        }

        $rcids = array_unique(array_values($rendered));

        $ret = [];
        foreach ($rcids as $rcid) {
            $ret[] = $candidates[$rcid];
        }

        return $ret;
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
            $prev = $items[$i - 1];
            $curr = $items[$i];

            $prevterm_last_token_pos = $prev->pos + $prev->length - 1;
            $overlap = $prevterm_last_token_pos - $curr->pos + 1;

            if ($overlap > 0) {
                $zws = mb_chr(0x200B);
                $curr_tokens = explode($zws, $curr->text);
                $show = array_slice($curr_tokens, $overlap);
                $curr->displaytext = implode($zws, $show);
            }
        }

        return $items;
    }

    public function main($language, $words, $texttokens) {
        // $time_now = microtime(true);
        $renderable = $this->get_renderable($language, $words, $texttokens);
        // dump('got initial renderable:');
        // dump($renderable);
        // dump('get renderable: ' . (microtime(true) - $time_now));
        $items = $this->sort_by_order_and_tokencount($renderable);
        $items = $this->calc_overlaps($items);
        return $items;
    }

    public static function getRenderable(Language $lang, $words, $texttokens) {
        // dump('called getRenderable');
        $rc = new RenderableCalculator();
        return $rc->main($lang, $words, $texttokens);
    }

}
