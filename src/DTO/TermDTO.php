<?php

namespace App\DTO;

use App\Entity\Language;
use App\Entity\Term;
use App\Domain\Dictionary;
use App\Repository\TermTagRepository;

class TermDTO
{

    public ?int $id = null;

    public ?Language $language = null;

    public ?string $Text = null;

    public ?int $Status = 1;

    public ?string $Translation = null;

    public ?string $Romanization = null;

    public ?string $Sentence = null;

    public ?int $WordCount = null;

    public array $termTags;

    public ?string $ParentText = null;

    public ?string $CurrentImage = null;

    public function __construct()
    {
        $this->termTags = array();
    }


    /**
     * Convert the given TermDTO to a Term.
     */
    public static function buildTerm(TermDTO $dto, Dictionary $dictionary, TermTagRepository $ttr): Term
    {
        if (is_null($dto->language)) {
            throw new \Exception('Language not set for term dto');
        }
        if (is_null($dto->Text)) {
            throw new \Exception('Text not set for term dto');
        }

        $t = $dictionary->find($dto->Text, $dto->language);
        if ($t == null)
            $t = new Term();

        $t->setLanguage($dto->language);
        $t->setText($dto->Text);
        $t->setStatus($dto->Status);
        $t->setTranslation($dto->Translation);
        $t->setRomanization($dto->Romanization);
        $t->setSentence($dto->Sentence);
        if ($dto->WordCount != null)
            $t->setWordCount($dto->WordCount);
        $t->setCurrentImage($dto->CurrentImage);

        $termtags = array();
        foreach ($dto->termTags as $s) {
            $termtags[] = $ttr->findOrCreateByText($s);
        }

        $parent = TermDTO::findOrCreateParent($dto, $dictionary, $termtags);
        $t->setParent($parent);

        $t->removeAllTermTags();
        foreach ($termtags as $tt) {
            $t->addTermTag($tt);
        }

        return $t;
    }

    private static function findOrCreateParent(TermDTO $dto, Dictionary $dictionary, array $termtags): ?Term
    {
        $pt = $dto->ParentText;
        if ($pt == null || $pt == '')
            return null;

        $p = $dictionary->find($pt, $dto->language);

        if ($p !== null)
            return $p;

        $p = new Term();
        $p->setText($pt);
        $p->setLanguage($dto->language);
        $p->setStatus($dto->Status);
        $p->setTranslation($dto->Translation);
        $p->setSentence($dto->Romanization);
        foreach ($termtags as $tt)
            $p->addTermTag($tt);

        return $p;
    }

}