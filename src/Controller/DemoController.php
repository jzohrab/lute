<?php

namespace App\Controller;

use App\Repository\LanguageRepository;
use App\Repository\TextRepository;
use App\Domain\Dictionary;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Utils\Connection;
use App\Utils\MigrationHelper;

class DemoController extends AbstractController
{

    #[Route('/demo/load', name: 'app_demo_load', methods: ['GET'])]
    public function load_demo(LanguageRepository $langrepo, TextRepository $textrepo, Dictionary $dictionary): Response
    {
        if (! MigrationHelper::isEmptyDemo()) {
            return $this->redirectToRoute('app_index');
        }
        MigrationHelper::loadDemoData($langrepo, $textrepo, $dictionary);
        return $this->redirectToRoute('app_index', [ 'tutorialloaded' => true ]);
    }


}
