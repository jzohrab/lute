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

    // Note that symfony has problems handling periods in image params!
    // Ref https://github.com/symfony/symfony/issues/25541.
    #[Route('/{lgid}/{term}', name: 'app_userimages', methods: ['GET'])]
    public function get_image(int $lgid, string $term)
    {
        $filename = __DIR__ . '/../../data/userimages/' . $lgid . '/' . $term . '.jpeg';
        $stream = new Stream($filename);
        $response = new BinaryFileResponse($stream);
        return $response;
    }

}
