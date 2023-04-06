<?php
/**
 * Create an empty migration script in migrations/.
 *
 * Call:
 * php create_migration_script.php create_table_blah
 *
 * creates a file like 'migrations/yyyymmdd_hhmmss_create_table_blah.sql
 */

$outdir = __DIR__ . "/migrations";

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

echo "New migration file: $filename\n\n";

?>