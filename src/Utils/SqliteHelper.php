<?php

namespace App\Utils;

require_once __DIR__ . '/../../db/lib/pdo_migrator.php';

use Symfony\Component\Filesystem\Path;
use App\Utils\Connection;

// Class for namespacing only.
class SqliteHelper {

    public static function DbFilename(): string {
        $filename = $_ENV['DB_FILENAME'];
        // Special token used by symfony to indicate root:
        $symftoken = '%kernel.project_dir%';
        if (str_contains($filename, $symftoken)) {
            $projdir = __DIR__ . '/../..';
            $projdir = Path::canonicalize($projdir);
            $filename = str_replace($symftoken, $projdir, $filename);
        }
        return $filename;
    }

    private static function getMigrator($showlogging = false) {
        $dir = __DIR__ . '/../../db/migrations';
        $repdir = __DIR__ . '/../../db/migrations_repeatable';
        $pdo = Connection::getFromEnvironment();
        $m = new \PdoMigrator($pdo, $dir, $repdir, $showlogging);
        return $m;
    }

    public static function CreateDb() {
        $baseline = __DIR__ . '/../../db/baseline/baseline.db';
        $dest = SqliteHelper::DbFilename();
        copy($baseline, $dest);
        SqliteHelper::runMigrations();
    }

    public static function runMigrations($showlogging = false) {
        $m = SqliteHelper::getMigrator($showlogging);
        $m->process();
    }

    public static function hasPendingMigrations() {
        $m = SqliteHelper::getMigrator(false);
        return count($m->get_pending()) > 0;
    }

    /**
     * Used by public/index.php to initiate and migrate the database.
     *
     * Returns [ messages, error string ]
     */
    public static function doSetup(): array {
        $messages = [];
        $error = null;

        // Verify env vars.
        $config_issues = [];
        $dbf = $_ENV['DB_FILENAME'] ?? '';
        $dbf = trim($dbf);
        if ($dbf == '')
            $config_issues[] = 'Missing key DB_FILENAME';

        if (count($config_issues) > 0) {
            $args = [ 'errors' => $config_issues ];
            $error = SqliteHelper::renderError('config_error.html.twig', $args);
            return [ $messages, $error ];
        }

        return [ 'ok', [] ];
        /*
        $newdbcreated = false;
        try {
            MysqlHelper::verifyConnectionParams();

            $dbexists = MysqlHelper::databaseExists();

            if ($dbexists && MysqlHelper::isLearningWithTextsDb()) {
                [ $server, $userid, $passwd, $dbname ] = MysqlHelper::getParams();
                $args = [
                    'dbname' => $dbname,
                    'username' => $userid
                ];
                $error = MysqlHelper::renderError('will_not_migrate_lwt_automatically.html.twig', $args);
                return [ $messages, $error ];
            }

            if (! $dbexists) {
                MysqlHelper::createBlankDatabase();
                MysqlHelper::installBaseline();
                $newdbcreated = true;
                $messages[] = 'New database created.';
            }

            if (MysqlHelper::hasPendingMigrations()) {
                MysqlHelper::runMigrations();
                if (! $newdbcreated) {
                    $messages[] = 'Database updated.';
                }
            }
        }
        catch (\Exception $e) {
            $args = ['errors' => [ $e->getMessage() ]];
            $error = MysqlHelper::renderError('fatal_error.html.twig', $args);
        }

        return [ $messages, $error ];
        */
    }

    private static function renderError($name, $args = []): string {
        // ref https://twig.symfony.com/doc/2.x/api.html
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../templates/errors');
        $twig = new \Twig\Environment($loader);
        $template = $twig->load($name);
        return $template->render($args);
    }

}

?>