<?php

namespace App\Utils;

require_once __DIR__ . '/../../db/lib/mysql_migrator.php';

// Class for namespacing only.
class MigrationHelper {

    private static function getOrThrow($key) {
        if (! isset($_ENV[$key]))
            throw new \Exception("Missing ENV key $key");
        $ret = $_ENV[$key];
        if ($ret == null || $ret == '')
            throw new \Exception("Empty ENV key $key");
        return $ret;
    }

    private static function getMigrator($showlogging = false) {
        $server = MigrationHelper::getOrThrow('DB_HOSTNAME');
        $userid = MigrationHelper::getOrThrow('DB_USER');
        $passwd = MigrationHelper::getOrThrow('DB_PASSWORD');
        $dbname = MigrationHelper::getOrThrow('DB_DATABASE');

        $dir = __DIR__ . '/../../db/migrations';
        $repdir = __DIR__ . '/../../db/migrations_repeatable';
        $migration = new \MysqlMigrator($dir, $repdir, $server, $dbname, $userid, $passwd, $showlogging);
        return $migration;
    }

    /**
     * Used by public/index.php to initiate and migrate the database.
     * 
     * Feels kind of messy, not sure where this belongs, but it will do
     * until proven poor.  ... only tested manually.
     *
     * Returns [ messages, error string ]
     */
    public static function doSetup(): array {

        $messages = [];
        $error = null;
        $newdbcreated = false;
        try {
            Connection::verifyConnectionParams();

            if (MigrationHelper::isLearningWithTextsDb()) {
                $args = [
                    'dbname' => MigrationHelper::getOrThrow('DB_DATABASE'),
                    'username' => MigrationHelper::getOrThrow('DB_USER')
                ];
                $error = MigrationHelper::renderError('will_not_migrate_lwt_automatically.html.twig', $args);
                return [ $messages, $error ];
            }

            if (! Connection::databaseExists()) {
                Connection::createBlankDatabase();
                MigrationHelper::installBaseline();
                $newdbcreated = true;
                $messages[] = 'New database created.';
            }
            if (MigrationHelper::hasPendingMigrations()) {
                MigrationHelper::runMigrations();
                if (! $newdbcreated) {
                    $messages[] = 'Database updated.';
                }
            }
        }
        catch (\Exception $e) {
            $args = ['errors' => [ $e->getMessage() ]];
            $error = DatabaseSetup::renderError('fatal_error.html.twig', $args);
        }

        return [ $messages, $error ];
    }

    private static function renderError($name, $args = []): string {
        // ref https://twig.symfony.com/doc/2.x/api.html
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../templates');
        $twig = new \Twig\Environment($loader);
        $template = $twig->load($name);
        return $template->render($args);
    }

    public static function hasPendingMigrations() {
        $migration = MigrationHelper::getMigrator();
        return count($migration->get_pending()) > 0;
    }

    public static function runMigrations($showlogging = false) {
        $dbname = MigrationHelper::getOrThrow('DB_DATABASE');
        $migration = MigrationHelper::getMigrator($showlogging);
        $migration->exec("ALTER DATABASE `{$dbname}` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $migration->process();
    }

    public static function installBaseline() {
        $files = [
            'baseline_schema.sql',
            'reference_data.sql'
        ];
        foreach ($files as $f) {
            $basepath = __DIR__ . '/../../db/baseline/';
            MigrationHelper::process_file($basepath . $f);
        }
    }

    private static function process_file($file) {
        $conn = Connection::getFromEnvironment();
        $commands = file_get_contents($file);
        $conn->multi_query($commands);
        do {
            $conn->store_result();
        } while ($conn->next_result());
        
        if ($conn->error) {
            throw new \Exception($conn->error);
        }
    }

    public static function isLuteDemo() {
        $dbname = MigrationHelper::getOrThrow('DB_DATABASE');
        return ($dbname == 'lute_demo');
    }

    public static function isEmptyDemo() {
        if (! MigrationHelper::isLuteDemo())
            return false;

        $conn = Connection::getFromEnvironment();
        $check = $conn
               ->query('select count(*) as c from Languages')
               ->fetch_array();
        $c = intval($check['c']);
        return $c == 0;
    }

    public static function isLearningWithTextsDb() {
        $dbname = MigrationHelper::getOrThrow('DB_DATABASE');
        $sql = "select count(*) as c from information_schema.tables
          where table_schema = '{$dbname}'
          and table_name = '_lwtgeneral'";
        $conn = Connection::getFromEnvironment();
        $check = $conn
               ->query($sql)
               ->fetch_array();
        $c = intval($check['c']);
        return $c == 1;
    }

    public static function loadDemoData() {
        $files = [
            'data.sql'
        ];
        foreach ($files as $f) {
            $basepath = __DIR__ . '/../../db/demo/';
            MigrationHelper::process_file($basepath . $f);
        }
    }

}

?>