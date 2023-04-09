<?php

namespace App\Controller;

use App\Repository\TextRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Utils\Connection;
use App\Utils\MysqlHelper;
use App\Utils\ImportCSV;
use App\Utils\AppManifest;
use App\Utils\SqliteBackup;
use App\Repository\SettingsRepository;

class IndexController extends AbstractController
{

    private function get_current_text(SettingsRepository $repo, TextRepository $trepo) {
        $tid = $repo->getCurrentTextID();
        if ($tid == null)
            return [null, null];
        $txt = $trepo->find($tid);
        if ($txt == null)
            return [null, null];
        return [ $txt->getID(), $txt->getTitle() ];
    }


    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(Request $request, SettingsRepository $repo, TextRepository $trepo): Response
    {
        [ $txid, $txtitle ] = $this->get_current_text($repo, $trepo);

        // DemoController sets tutorialloaded.
        $tutorialloaded = $request->query->get('tutorialloaded');

        $m = AppManifest::read();
        $gittag = $m['tag'];

        $bkp = new SqliteBackup($_ENV, $repo);
        $bkp_warning = $bkp->warning();

        if ($bkp->should_run_auto_backup()) {
            return $this->redirectToRoute(
                'app_backup_index',
                [ ],
                Response::HTTP_SEE_OTHER
            );
        }

        $show_import_link = count(ImportCSV::DbLoadedTables()) == 0;

        return $this->render('index.html.twig', [
            // TODO:sqlite fix MigrationHelper refs
            'isdemodb' => false, // MysqlHelper::isLuteDemo(),
            'demoisempty' => false, // MysqlHelper::isEmptyDemo(),
            'version' => $gittag,
            'tutorialloaded' => $tutorialloaded,
            'currtxid' => $txid,
            'currtxtitle' => $txtitle,
            'showimportcsv' => $show_import_link,
            'bkp_missing_enabled_key' => $bkp->missing_enabled_key(),
            'bkp_enabled' => $bkp->is_enabled(),
            'bkp_missing_keys' => !$bkp->config_keys_set(),
            'bkp_missing_keys_list' => $bkp->missing_keys(),
            'bkp_show_warning' => $bkp->is_enabled() && ($bkp_warning != ''),
            'bkp_warning' => $bkp_warning,
        ]);
    }

    #[Route('/server_info', name: 'app_server_info', methods: ['GET'])]
    public function server_info(): Response
    {
        $m = AppManifest::read();
        $commit = $m['commit'];
        $gittag = $m['tag'];
        $releasedate = $m['release_date'];

        $serversoft = explode(' ', $_SERVER['SERVER_SOFTWARE']);
        $apache = "Apache/?";
        if (substr($serversoft[0], 0, 7) == "Apache/") { 
            $apache = $serversoft[0]; 
        }
        $php = phpversion();

        $conn = Connection::getFromEnvironment();

        return $this->render('server_info.html.twig', [
            'tag' => $gittag,
            'commit' => $commit,
            'release_date' => $releasedate,

            'serversoft' => $serversoft,
            'apache' => $apache,
            'php' => $php,
            'dbname' => $_ENV['DB_DATABASE'],
            'server' => $_ENV['DB_HOSTNAME'],
            'symfconn' => $_ENV['DATABASE_URL'],
            'webhost' => $_SERVER['HTTP_HOST'],

            'isdev' => ($_ENV['APP_ENV'] == 'dev'),
            'allenv' => getenv(),
            'ENV' => $_ENV,
            'SERVER' => $_SERVER
        ]);
    }

}
