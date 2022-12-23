<?php
/**
 * Migrates the Lute db defined in connect.inc.php.
 */

require_once __DIR__ . '/../../connect.inc.php';
require_once __DIR__ . '/mysql_migrator.php';

// Class for namespacing only.
class MigrationHelper {

    public static function apply_migrations($showlogging = false) {
        global $server, $dbname, $userid, $passwd;
        echo "\nMigrating $dbname on $server.\n";

        $dir = __DIR__ . '/../migrations';
        $repdir = __DIR__ . '/../migrations_repeatable';
        $migration = new MysqlMigrator($dir, $repdir, $server, $dbname, $userid, $passwd, $showlogging);
        $migration->exec("ALTER DATABASE `{$dbname}` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $migration->process();
    }

    public static function get_pending_migrations() {
        global $server, $dbname, $userid, $passwd;
        $dir = __DIR__ . '/../migrations';
        $repdir = __DIR__ . '/../migrations_repeatable';
        $migration = new MysqlMigrator($dir, $repdir, $server, $dbname, $userid, $passwd);
        return $migration->get_pending();
    }

}

?>