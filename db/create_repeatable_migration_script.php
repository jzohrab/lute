<?php
/**
 * Create an empty migration script in migrations_repeatable/.
 *
 * Call:
 * php create_repeatable_migration_script.php create_trigger_blah
 *
 * creates a file like 'migrations_repeatable/yyyymmdd_hhmmss_create_trigger_blah.sql
 */

$outdir = __DIR__ . "/migrations_repeatable";

if (count($argv) != 2) {
    echo "\nPlease supply new script name base.\n\n";
    die();
}

$name = array_pop($argv);
$d = date("Ymd_His");
$filename = "{$outdir}/{$d}_{$name}.sql";

$f = fopen($filename, "w") or die("Unable to open file!");
fwrite($f, "-- fill this in");
fclose($f);

echo "New repeatable migration file: $filename\n\n";

?>