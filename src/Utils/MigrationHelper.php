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

}

?>