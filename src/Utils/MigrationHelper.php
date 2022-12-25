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
    
    public static function apply_migrations($showlogging = false) {
        $dbname = MigrationHelper::getOrThrow('DB_DATABASE');
        $migration = MigrationHelper::getMigrator($showlogging);
        $migration->exec("ALTER DATABASE `{$dbname}` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $migration->process();
    }


    public static function get_pending_migrations($showlogging = false) {
        $migration = MigrationHelper::getMigrator($showlogging);
        return $migration->get_pending();
    }

    public static function hasPendingMigrations() {
        $migration = MigrationHelper::getMigrator();
        return count($migration->get_pending()) > 0;
    }

}

?>