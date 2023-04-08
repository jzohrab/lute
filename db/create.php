<?php
use Symfony\Component\Dotenv\Dotenv;
use App\Utils\SqliteHelper;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$appenv = $_ENV['APP_ENV'];
$f = SqliteHelper::DbFilename();

if (strtolower($appenv) != 'test') {
    $msg = "
==============================
DANGER! Replacing non-test db.
==============================

You are going to REPLACE db
{$f},
which may have data in it.

This could be really really bad.

To confirm that you want to create this db,
please type 'confirm' at the prompt.

";
    echo $msg;

    $ret = readline('type confirm here: ');

    if ($ret != 'confirm') {
        echo "\nQuitting ...\n\n";
        return;
    }
}

SqliteHelper::CreateDb();
echo "\nCreated new db {$f}\n\n";
?>