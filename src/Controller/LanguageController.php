<?php

namespace App\Controller;

use App\Entity\Language;
use App\Form\LanguageType;
use App\Repository\LanguageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/language')]
class LanguageController extends AbstractController
{
    #[Route('/index', name: 'app_language_index', methods: ['GET'])]
    public function index(LanguageRepository $languageRepository): Response
    {
        return $this->render('language/index.html.twig', [
            'languages' => $languageRepository->findAll(),
        ]);
    }

    #[Route('/new/{template?-}', name: 'app_language_new', methods: ['GET', 'POST'])]
    public function new(string $template, Request $request, LanguageRepository $languageRepository): Response
    {
        $predefined = array_values(Language::getPredefined());
        $language = new Language();
        $template_lc = strtolower($template);
        $cands = array_filter($predefined, fn($p) => $template_lc == strtolower($p->getLgName()));
        if (count($cands) > 0) {
            // dump($cands);
            $language = array_values($cands)[0];
        }

        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $languageRepository->save($language, true);
            return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
        }


        $langnames = array_map(fn($p) => $p->getLgName(), $predefined);
        array_unshift($langnames, '-');
        return $this->renderForm('language/new.html.twig', [
            'predefined' => $langnames,
            'language' => $language,
            'form' => $form,
        ]);
    }

    #[Route('/{LgID}/edit', name: 'app_language_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Language $language, LanguageRepository $languageRepository): Response
    {
        $form = $this->createForm(LanguageType::class, $language);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $languageRepository->save($language, true);

            return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('language/edit.html.twig', [
            'language' => $language,
            'form' => $form,
        ]);
    }

    #[Route('/{LgID}', name: 'app_language_delete', methods: ['POST'])]
    public function delete(Request $request, Language $language, LanguageRepository $languageRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$language->getLgID(), $request->request->get('_token'))) {
            $languageRepository->remove($language, true);
        }

        return $this->redirectToRoute('app_language_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/jsonlist', name: 'app_language_jsonlist', methods: ['GET'])]
    public function jsonlist(LanguageRepository $languageRepository): Response
    {
        $langs = $languageRepository->findAll();
        $ret = [];
        foreach ($langs as $lang) {
            $termdicts = [
                $lang->getLgDict1URI(),
                $lang->getLgDict2URI()
            ];
            $termdicts = array_filter($termdicts, fn($s) => $s != null);
            $h = [
                'term' => $termdicts,
                'sentence' => $lang->getLgGoogleTranslateURI()
            ];
            $ret[$lang->getLgID()] = $h;
        }
        return $this->json($ret);
    }

}
