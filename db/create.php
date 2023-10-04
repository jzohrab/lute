<?php
use App\Utils\MyDotenv;
use App\Utils\SqliteHelper;

require __DIR__ . '/../vendor/autoload.php';

MyDotenv::boot(__DIR__ . '/../.env');

$appenv = $_ENV['APP_ENV'];

$dbfilename = $_ENV['DB_FILENAME'];

if (! str_contains($dbfilename, 'test_lute.db')) {
    $msg = "\n\n
NOT A TESTING DB
----------------
Db = {$dbfilename} ... MUST contain test_lute.db\n\n";
    throw new \Exception($msg);
}
else {
    SqliteHelper::CreateDb();
    echo "\nCreated new db {$f}\n\n";
}
?>