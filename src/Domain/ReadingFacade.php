<?php

namespace App\Domain;

use DateTime;
use App\Entity\Text;
use App\Entity\Term;
use App\Entity\Language;
use App\DTO\TermDTO;
use App\DTO\TextToken;
use App\Entity\Status;
use App\Entity\Sentence;
use App\Repository\TextRepository;
use App\Repository\BookRepository;
use App\Repository\TermTagRepository;
use App\Repository\TermRepository;
use App\Domain\TermService;
use App\Utils\Connection;

class ReadingFacade {

    private TermRepository $term_repo;
    private TextRepository $textrepo;
    private BookRepository $bookrepo;
    private TermService $term_service;
    private TermTagRepository $termtagrepo;

    public function __construct(
        TermRepository $term_repo,
        TextRepository $textrepo,
        BookRepository $bookrepo,
        TermService $term_service,
        TermTagRepository $termTagRepository
    ) {
        $this->term_repo = $term_repo;
        $this->term_service = $term_service;
        $this->textrepo = $textrepo;
        $this->bookrepo = $bookrepo;
        $this->termtagrepo = $termTagRepository;
    }

    
    public function getSentences(Text $text)
    {
        if ($text->getID() == null)
            return [];

        if ($text->isArchived()) {
            $text->setArchived(false);
            $this->textrepo->save($text, true);
        }

        $tokens = $this->getTextTokens($text);
        if (count($tokens) == 0) {
            $text->getBook()->fullParse();
            $tokens = $this->getTextTokens($text);
        }

        $terms = $this->term_repo->findTermsInText($text);

        $renderableSentences = [];
        $usenums = array_map(fn($t) => $t->TokSentenceNumber, $tokens);
        $usenums = array_unique($usenums);
        foreach ($usenums as $senum) {
            $setokens = array_filter($tokens, fn($t) => $t->TokSentenceNumber == $senum);
            $renderable = RenderableCalculator::getRenderable($terms, $setokens);
            $textitems = array_map(
                fn($i) => $i->makeTextItem($senum, $text->getID(), $text->getLanguage()->getLgID()),
                $renderable
            );
            $rs = new RenderableSentence($senum, $textitems);
            $renderableSentences[] = $rs;
        }

        return $renderableSentences;
    }

    private function getTextTokens(Text $t): array {
        $textid = $t->getID();
        if ($textid == null)
            return [];

        $sql = "select
          TokSentenceNumber,
          TokOrder,
          TokIsWord,
          TokText,
          TokTextLC
          from texttokens
          where toktxid = $textid
          order by TokSentenceNumber, TokOrder";

        $conn = Connection::getFromEnvironment();
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $ret = [];
        foreach ($rows as $row) {
            $tok = new TextToken();
            $tok->TokSentenceNumber = intval($row['TokSentenceNumber']);
            $tok->TokOrder = intval($row['TokOrder']);
            $tok->TokIsWord = intval($row['TokIsWord']);
            $tok->TokText = $row['TokText'];
            $tok->TokTextLC = $row['TokTextLC'];
            $ret[] = $tok;
        }
        return $ret;
    }

    public function mark_read(Text $text) {
        $text->setReadDate(new DateTime("now"));
        $this->textrepo->save($text, true);
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
            return $ti->IsWord == 1 && ($ti->WoID == 0 || $ti->WoID == null) && $ti->TokenCount == 1;
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
                if ($this->term_service->find($t->getText(), $lang) != null) {
                    // Skip to the next item.
                    continue;
                }
            }

            $t->setStatus(Status::WELLKNOWN);
            $this->term_service->add($t, false);
            $i += 1;
            if (($i % $batchSize) === 0) {
                $this->term_service->flush();
            }
        }
        // Remaining items.
        $this->term_service->flush();
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
            $t = $this->term_service->findOrNew($lang, $u);
            $t->setLanguage($lang);
            $t->setStatus($newstatus);
            $this->term_service->add($t, false);
            $i += 1;
            if (($i % $batchSize) === 0) {
                $this->term_service->flush();
            }
        }
        // Remaining items.
        $this->term_service->flush();
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

    /**
     * Get fully populated Term from database, or create a new one with available data.
     *
     * @param Language the term lang
     * @param text string
     *
     * @return TermDTO
     */
    public function loadDTO(Language $language, string $text): TermDTO {
        $term = $this->term_service->findOrNew($language, $text);
        $dto = $term->createTermDTO();
        if ($term->getFlashMessage() != null) {
            //// $term->popFlashMessage();
            //// $this->term_service->add($term, true);
            //// $this->term_service->flush();
            //
            // Annoying ... I wanted any flash messages to be deleted
            // when the DTO was loaded.  Popping the flash message and
            // then saving it via the term service should have worked,
            // but it never did.  Term service tests showed that
            // popping the message and saving the term did in fact
            // remove the message, but whenever I tried to use that
            // here it never worked!  Using the blunt instrument
            // "killFlashMessageFor" to just kill it in the database.
            $this->term_service->killFlashMessageFor($term);
        }
        return $dto;
    }


    /** Save a term. */
    public function saveDTO(TermDTO $termdto): void {
        $term = TermDTO::buildTerm(
            $termdto, $this->term_service, $this->termtagrepo
        );
        $this->term_service->add($term);
    }

    /** Remove term. */
    public function removeDTO(TermDTO $dto) {
        $term = TermDTO::buildTerm(
            $dto, $this->term_service, $this->termtagrepo
        );
        $this->term_service->remove($term);
    }

}