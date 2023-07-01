<?php

namespace App\Controller;

use App\DTO\TermImportDTO;
use App\Form\TermImportDTOType;
use App\Repository\LanguageRepository;
use App\Repository\TermRepository;
use App\Repository\TermTagRepository;
use App\Domain\TermImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/termimport')]
class TermImportController extends AbstractController
{

    #[Route('/index', name: 'app_term_import_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        LanguageRepository $lang_repo,
        TermRepository $term_repo,
        TermTagRepository $termtag_repo
    ): Response
    {
        $dto = new TermImportDTO();
        $form = $this->createForm(TermImportDTOType::class, $dto);
        $svc = new TermImportService(
            $lang_repo,
            $term_repo,
            $termtag_repo
        );
        $resp = $this->processImport(
            $form, $request, $dto, $svc
        );
        if ($resp != null)
            return $resp;

        return $this->render('termimport/index.html.twig', [
            'form' => $form
        ]);
    }


    private function processImport(
        \Symfony\Component\Form\Form $form,
        Request $request,
        TermImportDTO $dto,
        TermImportService $svc
    ): ?Response
    {
        $form->handleRequest($request);
        if (! $form->isSubmitted())
            return null;
        if (! $form->isValid()) {
            $msg = "Error on submit: " . $form->getErrors(true, false);
            $this->addFlash('notice', $msg);
            return null;
        }

        try {
            // ref https://symfony.com/doc/current/controller/upload_file.html
            // throw new \Exception('hi there');

            $textfile = $form->get('TextFile')->getData();
            if ($textfile == null)
                throw new \Exception("Missing import file.");
            $fname = $textfile->getPathname();
            $stats = $svc->importFile($fname);
            $msg = "Imported {$stats['created']} terms (skipped {$stats['skipped']})";
            $this->addFlash('notice', $msg);
            return $this->redirectToRoute('app_index', [], Response::HTTP_SEE_OTHER);
        }
        catch (\Exception $e) {
            $msg = "Error on import: " . $e->getMessage();
            $this->addFlash('notice', $msg);
            return $this->redirectToRoute('app_term_import_index', [], Response::HTTP_SEE_OTHER);
        }

    }

}
