<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Utils\SqliteBackup;
use App\Repository\SettingsRepository;
use App\Utils\ImportCSV;
use App\Utils\SqliteHelper;

#[Route('/utils')]
class UtilsController extends AbstractController
{

    #[Route('/import_csv', name: 'app_import_csv', methods: ['GET'])]
    public function import_csv(): Response
    {
        $missing_files = ImportCSV::MissingFiles();
        $loaded_tables = ImportCSV::DbLoadedTables();
        $db_is_empty = count($loaded_tables) == 0;
        return $this->render(
            'utils/csv_import.html.twig',
            [
                'db_filename' => SqliteHelper::DbFilename(),
                'all_files_exist' => (count($missing_files) == 0),
                'missing_files' => $missing_files,
                'db_is_empty' => $db_is_empty,
                'loaded_tables' => $loaded_tables
            ]
        );
    }

    #[Route('/do_import_csv', name: 'app_do_import_csv', methods: ['POST'])]
    public function do_import_csv(): JsonResponse
    {
        try {
            $ret = ImportCSV::doImport();
            return $this->json($ret);
        }
        catch(\Exception $e) {
            return new JsonResponse(array('errmsg' => $e->getMessage()), 500);
        }
    }


    #[Route('/backup', name: 'app_backup_index', methods: ['GET'])]
    public function backup(Request $request): Response
    {
        $backuptype = 'automatic';
        if ($request->query->has('type'))
            $backuptype = 'manual';

        return $this->render('utils/backup.html.twig', [
            'backup_folder' => $_ENV['BACKUP_DIR'],
            'backuptype' => $backuptype
        ]);
    }

    #[Route('/do_backup', name: 'app_do_backup_index', methods: ['POST'])]
    public function do_backup(Request $request, SettingsRepository $repo): JsonResponse
    {
        $backuptype = 'automatic';
        $prms = $request->request->all();
        if (array_key_exists('type', $prms))
            $backuptype = $prms['type'];

        try {
            $b = new SqliteBackup($_ENV, $repo, strtolower($backuptype) == 'manual');
            $f = $b->create_backup();
            return $this->json($f);
        }
        catch(\Exception $e) {
            return new JsonResponse(array('errmsg' => $e->getMessage()), 500);
        }
    }

}
