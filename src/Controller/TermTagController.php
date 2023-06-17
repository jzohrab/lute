<?php

namespace App\Controller;

use App\Entity\TermTag;
use App\Form\TermTagType;
use App\Repository\TermTagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/termtag')]
class TermTagController extends AbstractController
{

    #[Route('/jsonlist', name: 'app_termtag_jsonlist', methods: ['GET'])]
    public function jsonlist(TermTagRepository $repo): JsonResponse
    {
        $tags = $repo->findAll();
        $ret = array_map(fn($t): string => $t->getText() ?? '<?>', $tags);
        sort($ret);
        return $this->json($ret);
    }

    #[Route('/index', name: 'app_termtag_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('termtag/index.html.twig');
    }

    #[Route('/datatables', name: 'app_termtag_datatables', methods: ['POST'])]
    public function datatables_source(Request $request, TermTagRepository $repo): JsonResponse
    {
        $parameters = $request->request->all();
        $data = $repo->getDataTablesList($parameters);
        $data["draw"] = $parameters['draw'];
        return $this->json($data);
    }

    private function processForm(
        \Symfony\Component\Form\Form $form,
        Request $request,
        TermTag $termtag,
        TermTagRepository $repo
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
            $repo->save($termtag, true);
        }
        catch (\Exception $e) {
            $msg = "Error on save: " . $e->getMessage();
            $this->addFlash('notice', $msg);
        }
        return $this->redirectToRoute('app_termtag_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/new', name: 'app_termtag_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TermTagRepository $repo): Response
    {
        $t = new TermTag();
        $form = $this->createForm(TermTagType::class, $t);
        $resp = $this->processForm($form, $request, $t, $repo);
        if ($resp != null)
            return $resp;

        return $this->renderForm('termtag/new.html.twig', [
            'termtag' => $t,
            'form' => $form
        ]);
    }

    #[Route('/{id}/delete', name: 'app_termtag_delete', methods: ['POST'])]
    public function delete(Request $request, TermTag $t, TermTagRepository $repo): Response
    {
        $repo->remove($t, true);
        return $this->redirectToRoute('app_termtag_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/{id}/edit', name: 'app_termtag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TermTag $termtag, TermTagRepository $repo): Response
    {
        $form = $this->createForm(TermTagType::class, $termtag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repo->save($termtag, true);
            return $this->redirectToRoute('app_termtag_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('termtag/edit.html.twig', [
            'termtag' => $termtag,
            'form' => $form,
        ]);
    }

}
