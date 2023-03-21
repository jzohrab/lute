<?php

namespace App\Controller;

use App\Entity\Text;
use App\Form\TextType;
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

    #[Route('/{TxID}/edit', name: 'app_text_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Text $text, TextRepository $textRepository, SettingsRepository $settingsRepository): Response
    {
        $form = $this->createForm(TextType::class, $text);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $textRepository->save($text, true);
            return $this->redirectToRoute('app_read', [ 'TxID' => $text->getID() ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('text/edit.html.twig', [
            'text' => $text,
            'form' => $form,
        ]);
    }

}
