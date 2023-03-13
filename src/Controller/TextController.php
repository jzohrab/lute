<?php

namespace App\Controller;

use App\Entity\Text;
use App\Form\TextType;
use App\Domain\TextStatsCache;
use App\Repository\TextRepository;
use App\Repository\SettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/text')]
class TextController extends AbstractController
{

    #[Route('/index/{search?}', name: 'app_text_index', methods: ['GET'])]
    public function index(?string $search): Response
    {
        // Can pass an initial search string.  If nothing is passed, $search = null.
        return $this->render('text/index.html.twig', [
            'status' => 'Active',
            'initial_search' => $search
        ]);
    }

    private function datatables_source(Request $request, TextRepository $repo, $archived = false): JsonResponse
    {
        TextStatsCache::refresh();
        $parameters = $request->request->all();
        $data = $repo->getDataTablesList($parameters, $archived);
        $data["draw"] = $parameters['draw'];
        return $this->json($data);
    }

    #[Route('/datatables/active', name: 'app_text_datatables_active', methods: ['POST'])]
    public function datatables_active_source(Request $request, TextRepository $repo): JsonResponse
    {
        return $this->datatables_source($request, $repo, false);
    }

    #[Route('/datatables/archived', name: 'app_text_datatables_archived', methods: ['POST'])]
    public function datatables_archived_source(Request $request, TextRepository $repo): JsonResponse
    {
        return $this->datatables_source($request, $repo, true);
    }

    #[Route('/archived', name: 'app_text_archived', methods: ['GET'])]
    public function archived(TextRepository $textRepository): Response
    {
        return $this->render('text/index.html.twig', [
            'status' => 'Archived'
        ]);
    }

    #[Route('/{TxID}/edit', name: 'app_text_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Text $text, TextRepository $textRepository, SettingsRepository $settingsRepository): Response
    {
        $form = $this->createForm(TextType::class, $text);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $textRepository->save($text, true);

            $currtext = $settingsRepository->getCurrentTextID();
            if ($currtext == $text->getID()) {
                return $this->redirectToRoute('app_read', [ 'TxID' => $text->getID() ], Response::HTTP_SEE_OTHER);
            }
            else {
                return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->renderForm('text/edit.html.twig', [
            'text' => $text,
            'form' => $form,
        ]);
    }

    #[Route('/{TxID}/delete', name: 'app_text_delete', methods: ['POST'])]
    public function delete(Request $request, Text $text, TextRepository $textRepository): Response
    {
        /* TODO:security - CSRF token for datatables actions.
        $tok = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$text->getID(), $tok)) {
            $textRepository->remove($text, true);
        }
        */
        $textRepository->remove($text, true);
        return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{TxID}/archive', name: 'app_text_archive', methods: ['POST'])]
    public function archive(Request $request, Text $text, TextRepository $textRepository): Response
    {
        /* TODO:security - CSRF token for datatables actions.
        $tok = $request->request->get('_token');
        if ($this->isCsrfTokenValid('archive'.$text->getID(), $tok)) {
            $text->setArchived(true);
            $textRepository->save($text, true);
        }
        */
        $text->setArchived(true);
        $textRepository->save($text, true);
        return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{TxID}/unarchive', name: 'app_text_unarchive', methods: ['POST'])]
    public function unarchive(Request $request, Text $text, TextRepository $textRepository): Response
    {
        /* TODO:security - CSRF token for datatables actions.
        $tok = $request->request->get('_token');
        if ($this->isCsrfTokenValid('unarchive'.$text->getID(), $tok)) {
            $text->setArchived(false);
            $textRepository->save($text, true);
        }
        */
        $text->setArchived(false);
        $textRepository->save($text, true);
        return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
    }

}
