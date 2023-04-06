<?php
use Symfony\Component\Dotenv\Dotenv;
use App\Utils\MigrationHelper;

require __DIR__ . '/../../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../../.env');

echo "\nMigrating {$_ENV['DB_DATABASE']}\n\n";
MigrationHelper::runMigrations(true);
?>