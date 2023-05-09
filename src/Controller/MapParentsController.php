<?php

namespace App\Controller;

use App\Entity\Term;
use App\Entity\Language;
use App\Repository\TermRepository;
use App\Repository\LanguageRepository;
use App\Domain\TermMappingService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Path;

#[Route('/mapparents')]
class MapParentsController extends AbstractController
{

    #[Route('/index', name: 'app_mapparents_index', methods: ['GET'])]
    public function lemma_index(LanguageRepository $languageRepository): Response
    {
        return $this->render('mapparents/index.html.twig', [
            'languages' => $languageRepository->findAll(),
        ]);
    }

    #[Route('/export_language/{LgID}', name: 'app_mapparents_language_export', methods: ['GET'])]
    public function lemma_export(Request $request, Language $language, TermRepository $term_repo): Response
    {
        $svc = new TermMappingService($term_repo);
        $outputdir = __DIR__ . '/../../data/parents';
        $outputdir = Path::canonicalize($outputdir);
        if (!is_dir($outputdir))
            mkdir($outputdir);
        $lgid = $language->getLgID();
        $outfile = "{$outputdir}/terms_{$lgid}.txt";
        $svc->lemma_export($language, $outfile);
        return $this->render('mapparents/export.html.twig', [
            'language' => $language->getLgName(),
            'outfile' => $outfile
        ]);
    }

    #[Route('/import/{LgID}', name: 'app_mapparents_import', methods: ['GET'])]
    public function lemma_import(Request $request, Language $language, LanguageRepository $lang_repo, TermRepository $term_repo): Response
    {
        set_time_limit(0);
        $svc = new TermMappingService($term_repo);
        $lgid = $language->getLgID();
        $inputfile = __DIR__ . "/../../data/parents/import_{$lgid}.txt";
        $inputfile = Path::canonicalize($inputfile);
        if (! file_exists($inputfile)) {
            return $this->render('mapparents/import.html.twig', [
                'error' => "Missing input file {$inputfile}",
                'infile' => $inputfile
            ]);
        }

        $mappings = TermMappingService::loadMappingFile($inputfile);
        $stats = $svc->mapParents($language, $lang_repo, $mappings);
        $mappings = null;
        $svc = null;
        // dump('got stats, returning');
        // dump('lg name = ' . $language->getLgName());

        return $this->render('mapparents/import.html.twig', [
            'error' => false,
            'language' => $language->getLgName(),
            'infile' => $inputfile,
            'created' => $stats['created'],
            'updated' => $stats['updated']
        ]);
    }

}
