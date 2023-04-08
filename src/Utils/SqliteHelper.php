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
        $baseline = __DIR__ . '/../../db/baseline/baseline.sqlite';
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

}

?>