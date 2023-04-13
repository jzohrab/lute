<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Stream;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/userimages')]
class ImageController extends AbstractController
{

    #[Route('/{lang}/{relpath}', name: 'app_userimages', methods: ['GET'])]
    public function get_image(int $lang, string $relpath): BinaryFileResponse
    {
        $filename = __DIR__ . '/../../data/userimages/' . $langid . '/' . $relpath;
        dump('getting file ' . $filename);
        $stream = new Stream($filename);
        $response = new BinaryFileResponse($stream);
        return $response;
    }

}
