<?php

namespace App\Utils;

use Symfony\Component\Filesystem\Path;

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

    public static function CreateDb() {
        $baseline = __DIR__ . '/../../db/baseline/baseline.sqlite';
        $dest = SqliteHelper::DbFilename();
        copy($baseline, $dest);
    }

    public static function hasPendingMigrations() {
        return false;
    }

}

?>