<?php

namespace App\Controller;

use App\Entity\TermTag;
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
}
