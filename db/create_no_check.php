<?php
/*
THIS FILE IS EXTREMELY DANGEROUS, BUT I ASSUME YOU KNOW WHAT YOU'RE DOING.

This just hammers the existing db, replacing it with the baseline.

Use this file at your peril.
 */
use App\Utils\SqliteHelper;
use App\Utils\MyDotenv;

require __DIR__ . '/../vendor/autoload.php';
MyDotenv::boot(__DIR__ . '/../.env.test');

SqliteHelper::CreateDb();
?>