<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Term;
use App\Entity\Language;
use App\DTO\TermDTO;
use App\Entity\Status;
use App\Entity\Sentence;
use App\Repository\ReadingRepository;
use App\Repository\TextRepository;
use App\Repository\TextItemRepository;
use App\Repository\SettingsRepository;
use App\Repository\TermTagRepository;
use App\Domain\Dictionary;

class ReadingFacade {

    private ReadingRepository $repo;
    private TextRepository $textrepo;
    private SettingsRepository $settingsrepo;
    private Dictionary $dictionary;
    private TermTagRepository $termtagrepo;

    public function __construct(
        ReadingRepository $repo,
        TextRepository $textrepo,
        SettingsRepository $settingsrepo,
        Dictionary $dictionary,
        TermTagRepository $termTagRepository
    ) {
        $this->repo = $repo;
        $this->dictionary = $dictionary;
        $this->textrepo = $textrepo;
        $this->settingsrepo = $settingsrepo;
        $this->termtagrepo = $termTagRepository;
    }

    
    public function getTextItems(Text $text)
    {
        return $this->repo->getTextItems($text);
    }

    private function buildSentences($textitems) {
        $textitems_by_sentenceid = array();
        foreach($textitems as $t) {
            $textitems_by_sentenceid[$t->SeID][] = $t;
        }

        $sentences = [];
        foreach ($textitems_by_sentenceid as $seid => $textitems)
            $sentences[] = new Sentence($seid, $textitems);

        return $sentences;
    }

    public function getSentences(Text $text)
    {
        if ($text->getID() == null)
            return [];

        if ($text->isArchived()) {
            $text->setArchived(false);
            $this->textrepo->save($text, true);
        }

        $tis = $this->repo->getTextItems($text);

        if (count($tis) == 0) {
            // Catch-all to clean up missing parsing data,
            // if the user has cleaned out the existing text items.
            // TODO:future:2023/02/01 - remove this, slow, when text re-rendering is done.
            $text->parse();
            $tis = $this->repo->getTextItems($text);
        }

        return $this->buildSentences($tis);
    }

    public function mark_unknowns_as_known(Text $text) {
        // Map any TextItems in the text to existing Terms.  This
        // ensures that we don't try to create new Terms for
        // TextItems, if that Term already exists.
        TextItemRepository::mapStringMatchesForText($text);

        $tis = $this->repo->getTextItems($text);

        $is_unknown = function($ti) {
            return $ti->WoID == 0 && $ti->WordCount == 1;
        };
        $unknowns = array_filter($tis, $is_unknown);
        $words_lc = array_map(fn($ti) => $ti->TextLC, $unknowns);
        $uniques = array_unique($words_lc, SORT_STRING);
        sort($uniques);
        $lang =$text->getLanguage();

        $batchSize = 100;
        $i = 0;
        foreach ($uniques as $u) {
            $t = new Term();
            $t->setLanguage($lang);
            $t->setText($u);
            $t->setStatus(Status::WELLKNOWN);
            $this->dictionary->add($t, false);
            $i += 1;
            if (($i % $batchSize) === 0) {
                $this->dictionary->flush();
            }
        }
        // Remaining items.
        $this->dictionary->flush();
    }

    public function update_status(Text $text, array $words, int $newstatus) {
        if (count($words) == 0)
            return;

        $uniques = array_unique($words, SORT_STRING);

        $lang =$text->getLanguage();
        $tid = $text->getID();

        $batchSize = 100;
        $i = 0;
        foreach ($uniques as $u) {
            $t = $this->repo->load(0, $tid, 0, $u);
            $t->setLanguage($lang);
            $t->setStatus($newstatus);
            $this->dictionary->add($t, false);
            $i += 1;
            if (($i % $batchSize) === 0) {
                $this->dictionary->flush();
            }
        }
        // Remaining items.
        $this->dictionary->flush();
    }

    public function get_prev_next(Text $text) {
        return $this->textrepo->get_prev_next($text);
    }

    public function set_current_text(Text $text) {
        $this->settingsrepo->saveCurrentTextID($text->getID());
    }


    private function get_textitems_for_term(Text $text, Term $term): array {
        // Use a temporary sentence to determine which items hide
        // which other items.
        $sentence = new Sentence(999, $this->getTextItems($text));
        $all_textitems = $sentence->getTextItems();

        $termid = $term->getID();
        $pid = null;
        $parent = $term->getParent();
        if ($parent != null)
            $pid = $parent->getID();

        // dump("term id = $termid, pid = $pid");
        $filt = function($t) use ($termid, $pid) {
            $tid = $t->WoID;
            // $ttxt = $t->Text;
            // dump("curr term id = $tid , and text = $ttxt");
            $ret = ($tid == $termid);
            if ($pid != null)
                $ret = $ret || ($tid == $pid);
            return $ret;
        };

        $ret = array_filter($all_textitems, $filt);
        return array_values($ret);
    }


    /**
     * Get fully populated Term from database, or create a new one with available data.
     *
     * @param wid  int    WoID, an actual ID, or 0 if new.
     * @param tid  int    TxID, text ID
     * @param ord  int    Ti2Order, the order in the text
     * @param text string Multiword text (overrides tid/ord text)
     *
     * @return TermDTO
     */
    public function loadDTO(int $wid = 0, int $tid = 0, int $ord = 0, string $text = ''): TermDTO {
        $term = $this->repo->load($wid, $tid, $ord, $text);
        return $term->createTermDTO();
    }


    /** Save a term, and return an array of UI updates. */
    public function saveDTO(TermDTO $termdto, int $textid): array {

        $term = TermDTO::buildTerm(
            $termdto, $this->dictionary, $this->termtagrepo
        );
        $text = $this->textrepo->find($textid);

        // Need to know if the term is new or not, because if it's a
        // multi-word term, it will be a *new element* in the rendered
        // HTML, and so will have to replace one of the elements it
        // hides.  Otherwise, it will just replace itself.
        $is_new = ($term->getID() == 0);

        $this->repo->save($term, true);
        return $this->getUIUpdates($text, $term, $is_new);
    }

    /** Remove term. */
    public function removeDTO(TermDTO $dto) {
        $term = TermDTO::buildTerm(
            $dto, $this->dictionary, $this->termtagrepo
        );
        $this->repo->remove($term, true);
    }

    /**
     * Get the UI items to replace and hide (delete).
     * Returns [ array of textitems to update, dict of span IDs -> replacements and hides ].
     */
    private function getUIUpdates(Text $text, Term $term, bool $is_new): array {
        $items = $this->get_textitems_for_term($text, $term);

        // what updates to do.
        $update_js = [];

        foreach ($items as $item) {
            $hide_ids = array_map(fn($i) => $i->getSpanID(), $item->hides);
            $hide_ids = array_values($hide_ids);

            $replace_id = $item->getSpanID();
            if ($is_new && count($hide_ids) > 0) {
                // New multiterm replaces the first element in the
                // list of things it hides, and the list of things it
                // hides is reduced.
                $replace_id = $hide_ids[0];
                $hide_ids = array_slice($hide_ids, 1);
            }

            $u = [
                'replace' => $replace_id,
                'hide' => $hide_ids
            ];
            $update_js[ $item->getSpanID() ] = $u;
        }

        return [
            $items,
            $update_js
        ];
            
    }

}