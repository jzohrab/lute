<?php

namespace App\Controller;

use App\Repository\TextRepository;
use App\Domain\Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Utils\Connection;
use App\Utils\MigrationHelper;
use App\Utils\AppManifest;

class IndexController extends AbstractController
{

    private function get_current_text($conn) {
        $sql = "select TxID, TxTitle from texts
           where txid = (
             select StValue from settings where StKey = 'currenttext'
           )";
        $rec = $conn
             ->query($sql)
             ->fetch_array();
        if (! $rec)
            return [null, null];
        else
            return [ $rec['TxID'], $rec['TxTitle'] ];
    }


    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $conn = Connection::getFromEnvironment();
        [ $txid, $txtitle ] = $this->get_current_text($conn);

        // DemoController sets tutorialloaded.
        $tutorialloaded = $request->query->get('tutorialloaded');

        $m = AppManifest::read();
        $gittag = $m['tag'];

        return $this->render('index.html.twig', [
            'isdemodb' => MigrationHelper::isLuteDemo(),
            'demoisempty' => MigrationHelper::isEmptyDemo(),
            'version' => $gittag,
            'tutorialloaded' => $tutorialloaded,
            'currtxid' => $txid,
            'currtxtitle' => $txtitle
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
        $mysql = $conn
               ->query("SELECT VERSION() as value")
               ->fetch_array()[0];

        return $this->render('server_info.html.twig', [
            'tag' => $gittag,
            'commit' => $commit,
            'release_date' => $releasedate,

            'serversoft' => $serversoft,
            'apache' => $apache,
            'php' => $php,
            'mysql' => $mysql,
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
