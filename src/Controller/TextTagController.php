<?php

namespace App\Controller;

use App\Entity\TextTag;
use App\Repository\TextTagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/texttag')]
class TextTagController extends AbstractController
{

    #[Route('/jsonlist', name: 'app_texttag_jsonlist', methods: ['GET'])]
    public function jsonlist(TextTagRepository $repo): JsonResponse
    {
        $tags = $repo->findAll();
        $ret = array_map(fn($t): string => $t->getText() ?? '<?>', $tags);
        sort($ret);
        return $this->json($ret);
    }
}
