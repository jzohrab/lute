<?php

// Front controller for the application.
//
// All requests are handed here via server rewrites, and then the
// kernel forwards it to the appropriate controller.


use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Utils\Connection;
use App\Utils\MigrationHelper;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');


$flash_messages = [];
try {
    Connection::verifyConnectionParams();
    if (! Connection::databaseExists()) {
        Connection::createBlankDatabase();
        MigrationHelper::installBaseline();
        $flash_messages[] = [ 'notice', 'New database created' ];
    }
    if (MigrationHelper::hasPendingMigrations()) {
        MigrationHelper::runMigrations();
        $flash_messages[] = [ 'notice', 'Database updated' ];
    }
}
catch (\Exception $e) {
    // ref https://twig.symfony.com/doc/2.x/api.html
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
    $twig = new \Twig\Environment($loader);
    $template = $twig->load('fatal_error.html.twig');
    echo $template->render(['errors' => [ $e->getMessage(), 'something', 'here' ]]);
    die();
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(
      explode(',', $trustedProxies),
      Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO
    );
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
