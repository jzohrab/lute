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
use App\Repository\BookRepository;
use App\Repository\SettingsRepository;
use App\Repository\TermTagRepository;
use App\Domain\Dictionary;

class ReadingFacade {

    private ReadingRepository $repo;
    private TextRepository $textrepo;
    private BookRepository $bookrepo;
    private SettingsRepository $settingsrepo;
    private Dictionary $dictionary;
    private TermTagRepository $termtagrepo;

    public function __construct(
        ReadingRepository $repo,
        TextRepository $textrepo,
        BookRepository $bookrepo,
        SettingsRepository $settingsrepo,
        Dictionary $dictionary,
        TermTagRepository $termTagRepository
    ) {
        $this->repo = $repo;
        $this->dictionary = $dictionary;
        $this->textrepo = $textrepo;
        $this->bookrepo = $bookrepo;
        $this->settingsrepo = $settingsrepo;
        $this->termtagrepo = $termTagRepository;
    }

    
    private function getRenderable($terms, $tokens) {
        $rc = new RenderableCalculator();
        $textitems = $rc->main($terms, $tokens);
        return $textitems;
    }

    public function getSentences(Text $text)
    {
        if ($text->getID() == null)
            return [];

        if ($text->isArchived()) {
            $text->setArchived(false);
            $this->textrepo->save($text, true);
        }

        $tokens = $this->repo->getTextTokens($text);
        if (count($tokens) == 0) {
            $text->parse();
            $tokens = $this->repo->getTextTokens($text);
        }

        $sentences = $this->repo->getSentences($text);
        $terms = $this->repo->getTermsInText($text);
        $tokens_by_senum = array();
        foreach ($tokens as $tok) {
            $tokens_by_senum[$tok->TokSentenceNumber][] = $tok;
        }

        $usenums = array_keys($tokens_by_senum);

        $lid = $text->getLanguage()->getLgID();
        $tid = $text->getID();
        $renderableSentences = [];
        foreach ($usenums as $senum) {
            $setokens = $tokens_by_senum[$senum];
            $renderable = $this->getRenderable($terms, $setokens);
            $textitems = array_map(
                fn($i) => $i->makeTextItem($senum, $tid, $lid),
                $renderable
            );
            $rs = new RenderableSentence($senum, $textitems);
            $renderableSentences[] = $rs;
        }

        return $renderableSentences;
    }


    public function mark_unknowns_as_known(Text $text) {
        $sentences = $this->getSentences($text);
        // dump($sentences);
        $tis = [];
        foreach ($sentences as $s) {
            foreach ($s->renderable() as $ti) {
                $tis[] = $ti;
            }
        }
        // dump($tis);

        $is_unknown = function($ti) {
            return $ti->IsWord == 1 && ($ti->WoID == 0 || $ti->WoID == null) && $ti->WordCount == 1;
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

            // In some cases, the parser thinks that a TextItem
            // contains text and punctuation (e.g., "Los últimos días
            // de Franklin Masacre" returned "no." as a text item).
            // When the textitem's raw text is compared against the
            // DB, it's not found, because the DB stores the Term as
            // "text{$zws}punct", where $zws is the zero-width space.
            // This results in an integrity violation (e.g for the
            // "Franklin" text, it fails with "duplicate key no.-1")
            // Ensure that a multi-work unknown isn't already saved.
            if ($t->getTokenCount() > 1) {
                if ($this->dictionary->find($t->getText(), $lang) != null) {
                    // Skip to the next item.
                    continue;
                }
            }

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
            $t = $this->repo->load($lang->getLgId(), $u);
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

    public function set_current_book_text(Text $text) {
        $b = $text->getBook();
        $b->setCurrentTextID($text->getId());
        $this->bookrepo->save($b, true);
    }

    public function get_prev_next(Text $text) {
        return $this->textrepo->get_prev_next($text);
    }

    public function get_prev_next_by_10(Text $text) {
        return $this->textrepo->get_prev_next_by_10($text);
    }

    public function set_current_text(Text $text) {
        $this->settingsrepo->saveCurrentTextID($text->getID());
    }


    /**
     * Get fully populated Term from database, or create a new one with available data.
     *
     * @param lid  int    LgID, language ID
     * @param text string
     *
     * @return TermDTO
     */
    public function loadDTO(int $lid, string $text): TermDTO {
        $term = $this->repo->load($lid, $text);
        return $term->createTermDTO();
    }


    /** Save a term. */
    public function saveDTO(TermDTO $termdto): void {
        $term = TermDTO::buildTerm(
            $termdto, $this->dictionary, $this->termtagrepo
        );
        $this->repo->save($term, true);
    }

    /** Remove term. */
    public function removeDTO(TermDTO $dto) {
        $term = TermDTO::buildTerm(
            $dto, $this->dictionary, $this->termtagrepo
        );
        $this->repo->remove($term, true);
    }


}