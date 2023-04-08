<?php
use Symfony\Component\Dotenv\Dotenv;
use App\Utils\SqliteHelper;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$f = SqliteHelper::DbFilename();
SqliteHelper::CreateDb();
echo "\nCreated new db {$f}\n\n";
?>