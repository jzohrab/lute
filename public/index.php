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

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

/**
 * Users create a connect.inc.php file with db settings, so for now
 * just use that to create the db connection.
 */
$connect_inc = __DIR__ . '/../connect.inc.php';
if (!file_exists($connect_inc)) {
    $content = "<html><body>
      <h1>Hi there!</h1>
      <p>You're missing the file connect.inc.php, in the root directory</p>
      <p>Please create the file from connect.inc.php.example.  (See the README for notes.)</p>
    </body></html>";
    $response = new Response($content);
    $response->send();
    die();
}

require_once $connect_inc;
global $userid, $passwd, $server, $dbname;
$DATABASE_URL = "mysql://{$userid}:{$passwd}@{$server}/{$dbname}?serverVersion=8&charset=utf8";
$_ENV['DATABASE_URL'] = $DATABASE_URL;
$_SERVER['DATABASE_URL'] = $DATABASE_URL;

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
