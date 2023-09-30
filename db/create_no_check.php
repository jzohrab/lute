<?php
/*
THIS FILE IS EXTREMELY DANGEROUS, BUT I ASSUME YOU KNOW WHAT YOU'RE DOING.

This just hammers the existing db, replacing it with the baseline.

Use this file at your peril.
 */
use App\Utils\SqliteHelper;
use App\Utils\MyDotenv;

$ds = DIRECTORY_SEPARATOR;
require __DIR__ . implode($ds, ['..', 'vendor', 'autoload.php']);
MyDotenv::boot(__DIR__ . implode($ds, ['..', '.env.test']);

SqliteHelper::CreateDb();
?>