<?php
/**
 * Migrates the Lute db defined in connect.inc.php.
 */

require_once __DIR__ . '/mysql_migrator.php';

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

    public static function apply_migrations($showlogging = false) {

        $server = MigrationHelper::getOrThrow('DB_HOSTNAME');
        $userid = MigrationHelper::getOrThrow('DB_USER');
        $passwd = MigrationHelper::getOrThrow('DB_PASSWORD');
        $dbname = MigrationHelper::getOrThrow('DB_DATABASE');

        echo "\nMigrating $dbname on $server.\n";

        $dir = __DIR__ . '/../migrations';
        $repdir = __DIR__ . '/../migrations_repeatable';
        $migration = new MysqlMigrator($dir, $repdir, $server, $dbname, $userid, $passwd, $showlogging);
        $migration->exec("ALTER DATABASE `{$dbname}` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $migration->process();
    }


    public static function get_pending_migrations() {
        $server = MigrationHelper::getOrThrow('DB_HOSTNAME');
        $userid = MigrationHelper::getOrThrow('DB_USER');
        $passwd = MigrationHelper::getOrThrow('DB_PASSWORD');
        $dbname = MigrationHelper::getOrThrow('DB_DATABASE');

        $dir = __DIR__ . '/../migrations';
        $repdir = __DIR__ . '/../migrations_repeatable';
        $migration = new MysqlMigrator($dir, $repdir, $server, $dbname, $userid, $passwd);
        return $migration->get_pending();
    }

}

?>