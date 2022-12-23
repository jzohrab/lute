<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$bootstrap = dirname(__DIR__).'/config/bootstrap.php';
if (file_exists($bootstrap)) {
    require $bootstrap;
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
