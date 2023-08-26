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
            $ret['trans'] = $t->getTranslation();

            $termTags = $t->getTermTags()->toArray();
            $tts = array_map(fn($tt) => $tt->getText(), $termTags);
            $ret['tags'] = $tts;
            return $ret;
        };

        $parentdata = [];
        $parent = $term->getParent();
        if ($parent != null && $parent->getTranslation() != $term->getTranslation())
            $parentdata[] = $makeArray($parent);

        $images = [ $term->getCurrentImage() ];
        if ($parent != null)
            $images[] = $parent->getCurrentImage();
        $images = array_filter($images, fn($i) => $i != null);
        $images = array_unique($images);

        return $this->render('termpopup/show.html.twig', [
            'term' => $term,
            'flashmsg' => $term->getFlashMessage(),
            'termTags' => $tts,
            'termImages' => $images,
            'parentdata' => $parentdata
        ]);
    }

}
