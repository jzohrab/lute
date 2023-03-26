<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Psr\Log\LoggerInterface;

#[Route('/utils')]
class UtilsController extends AbstractController
{

    #[Route('/backup', name: 'app_backup_index', methods: ['GET'])]
    public function backup(): Response
    {
        return $this->render('utils/backup.html.twig', [
        ]);
    }

}
