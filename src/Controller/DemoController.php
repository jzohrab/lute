<?php

namespace App\Controller;

use App\Repository\LanguageRepository;
use App\Repository\BookRepository;
use App\Domain\TermService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Utils\Connection;
use App\Utils\DemoDataLoader;
use App\Utils\SqliteHelper;

class DemoController extends AbstractController
{

    #[Route('/demo/done', name: 'app_demo_done', methods: ['GET'])]
    public function done_demo(): Response
    {
        SqliteHelper::clearDb();
        $this->addFlash('notice', "The database has been wiped clean.  Have fun!");
        return $this->redirectToRoute('app_index');
    }

}
