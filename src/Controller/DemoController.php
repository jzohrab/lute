<?php

namespace App\Controller;

use App\Repository\LanguageRepository;
use App\Repository\BookRepository;
use App\Domain\Dictionary;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Utils\Connection;
use App\Utils\DemoDataLoader;
use App\Utils\SqliteHelper;

class DemoController extends AbstractController
{

    #[Route('/demo/load', name: 'app_demo_load', methods: ['GET'])]
    public function load_demo(LanguageRepository $langrepo, BookRepository $bookrepo, Dictionary $dictionary): Response
    {
        if (! SqliteHelper::isEmptyDemo()) {
            return $this->redirectToRoute('app_index');
        }
        DemoDataLoader::loadDemoData($langrepo, $bookrepo, $dictionary);
        return $this->redirectToRoute('app_index', [ 'tutorialloaded' => true ]);
    }

    #[Route('/demo/done', name: 'app_demo_done', methods: ['GET'])]
    public function done_demo(): Response
    {
        // This is completely crazy, but it makes the user's life easier.  Maybe.
        // I hope this doesn't explode in my face.  It shouldn't.
        $envfile = __DIR__ . '/../../.env';
        $str=file_get_contents($envfile);
        $str=str_replace('lute_demo.db', 'lute.db', $str);
        file_put_contents($envfile, $str);

        // Sending back Javascript, b/c we want the browser to kick
        // off a completely new symfony call, reloading the .env
        // file we just edited. :-P
        $response = new Response();
        $response->setContent('<html><body><script>window.location="/";</script></body></html>');
        $response->headers->set('Content-Type', 'text/html');
        $response->setStatusCode(Response::HTTP_OK);
        return $response;
    }

}
