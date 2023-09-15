<?php

namespace App\DTO;

use App\Entity\Language;
use App\Entity\Term;
use App\Domain\TermService;
use App\Repository\TermTagRepository;

class TermDTO
{

    public ?int $id = null;

    public ?Language $language = null;

    /* The original text given to the DTO, to track changes. */
    public ?string $OriginalText = null;

    public ?string $Text = null;

    public ?int $Status = 1;

    public ?string $Translation = null;

    public ?string $Romanization = null;

    public ?int $TokenCount = null;

    public array $termTags;

    public ?string $FlashMessage = null;

    public array $termParents;

    public ?string $CurrentImage = null;

    public function __construct()
    {
        $this->termParents = array();
        $this->termTags = array();
    }

    public function textHasChanged(): bool {
        if (($this->OriginalText ?? '') == '')
            return false;
        return mb_strtolower($this->OriginalText) != mb_strtolower($this->Text);
    }

    /**
     * Convert the given TermDTO to a Term.
     */
    public static function buildTerm(TermDTO $dto, TermService $term_service, TermTagRepository $ttr): Term
    {
        if (is_null($dto->language)) {
            throw new \Exception('Language not set for term dto');
        }
        if (is_null($dto->Text)) {
            throw new \Exception('Text not set for term dto');
        }

        $t = $term_service->find($dto->Text, $dto->language);
        if ($t == null)
            $t = new Term();

        $t->setLanguage($dto->language);
        $t->setText($dto->Text);
        $t->setStatus($dto->Status);
        $t->setTranslation($dto->Translation);
        $t->setRomanization($dto->Romanization);
        $t->setCurrentImage($dto->CurrentImage);

        $termtags = array();
        foreach ($dto->termTags as $s) {
            $termtags[] = $ttr->findOrCreateByText($s);
        }
        $t->removeAllTermTags();
        foreach ($termtags as $tt) {
            $t->addTermTag($tt);
        }

        $termparents = array();
        $createparents = array_filter(
            $dto->termParents,
            fn($p) => $p != null && $p != '' && mb_strtolower($dto->Text) != mb_strtolower($p)
        );
        foreach ($createparents as $p) {
            $termparents[] = TermDTO::findOrCreateParent($p, $dto, $term_service, $termtags);
        }
        $t->removeAllParents();
        foreach ($termparents as $tp) {
            $t->addParent($tp);
        }

        return $t;
    }

    private static function findOrCreateParent(string $pt, TermDTO $dto, TermService $term_service, array $termtags): ?Term
    {
        $p = $term_service->find($pt, $dto->language);

        if ($p !== null) {
            if (($p->getTranslation() ?? '') == '')
                $p->setTranslation($dto->Translation);
            if (($p->getCurrentImage() ?? '') == '')
                $p->setCurrentImage($dto->CurrentImage);
            return $p;
        }

        $p = new Term();
        $p->setLanguage($dto->language);
        $p->setText($pt);
        $p->setStatus($dto->Status);
        $p->setTranslation($dto->Translation);
        $p->setCurrentImage($dto->CurrentImage);
        foreach ($termtags as $tt)
            $p->addTermTag($tt);

        return $p;
    }

}