<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/utils')]
class UtilsController extends AbstractController
{

    #[Route('/backup', name: 'app_backup_index', methods: ['GET'])]
    public function backup(): Response
    {
        return $this->render('utils/backup.html.twig', [
            'backup_folder' => 'TODO:BACKUP some folder'
        ]);
    }

    #[Route('/do_backup', name: 'app_do_backup_index', methods: ['POST'])]
    public function do_backup(): JsonResponse
    {
        try {
            throw new \Exception('blah');
            return $this->json('ok');
        }
        catch(\Exception $e) {
            return new JsonResponse(array('errmsg' => $e->getMessage()), 500);
        }
    }

}
