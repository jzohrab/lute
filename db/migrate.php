<?php
use App\Utils\MyDotenv;
use App\Utils\SqliteHelper;

require __DIR__ . '/../vendor/autoload.php';

MyDotenv::boot(__DIR__ . '/../.env');

$f = SqliteHelper::DbFilename();
echo "\nMigrating {$f} ...\n";
SqliteHelper::runMigrations(true);
echo "Done.\n\n";
?>