<?php

namespace App\Controller;

use App\Entity\Term;
use App\Entity\Language;
use App\DTO\TermDTO;
use App\Form\TermDTOType;
use App\Repository\TermRepository;
use App\Repository\TermTagRepository;
use App\Repository\LanguageRepository;
use App\Domain\TermService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/termpopup')]
class TermPopupController extends AbstractController
{

    #[Route('/{id}', name: 'app_termpopup_show', methods: ['GET'])]
    public function show(Term $term): Response
    {
        $termTags = $term->getTermTags()->toArray();
        $tts = array_map(fn($tt) => $tt->getText(), $termTags);

        $makeArray = function($t) {
            $ret = [];
            $ret['term'] = $t->getText();
            $ret['roman'] = $t->getRomanization();
            $ret['trans'] = $t->getTranslation() ?? '-';

            $termTags = $t->getTermTags()->toArray();
            $tts = array_map(fn($tt) => $tt->getText(), $termTags);
            $ret['tags'] = $tts;
            return $ret;
        };

        $parentterms = [];
        foreach ($term->getParents() as $p)
            $parentterms[] = $p->getTextLC();
        $parentterms = implode(', ', $parentterms);

        $parentdata = [];
        if (count($term->getParents()) == 1) {
            $parent = $term->getParents()->toArray()[0];
            if ($parent->getTranslation() != $term->getTranslation())
                $parentdata[] = $makeArray($parent);
        }
        else {
            foreach ($term->getParents() as $p)
                $parentdata[] = $makeArray($p);
        }

        $images = [ $term->getCurrentImage() ];
        foreach ($term->getParents() as $p)
            $images[] = $p->getCurrentImage();
        $images = array_filter($images, fn($i) => $i != null);
        $images = array_unique($images);

        return $this->render('termpopup/show.html.twig', [
            'term' => $term,
            'flashmsg' => $term->getFlashMessage(),
            'termTags' => $tts,
            'termImages' => $images,
            'parentdata' => $parentdata,
            'parentterms' => $parentterms
        ]);
    }

}
