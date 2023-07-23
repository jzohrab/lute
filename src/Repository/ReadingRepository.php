<?php

namespace App\Repository;

use App\Entity\Text;
use App\DTO\TextToken;
use App\Entity\TextSentence;
use App\Entity\Term;
use App\Entity\Sentence;
use App\Entity\TextItem;
use App\Entity\Language;
use App\Domain\TermService;
use Doctrine\ORM\EntityManagerInterface;
 

class ReadingRepository
{
    private EntityManagerInterface $manager;
    private TermService $term_service;
    private TermRepository $term_repo;

    public function __construct(
        EntityManagerInterface $manager,
        TermRepository $term_repo
    )
    {
        $this->manager = $manager;
        $this->term_service = new TermService($term_repo);
        $this->term_repo = $term_repo;
    }

    public function getTermsInText(Text $text) {
        return $this->term_repo->findTermsInText($text);
    }

}
