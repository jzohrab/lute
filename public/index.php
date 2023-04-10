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
use App\Utils\SqliteHelper;

use App\Entity\Text;

// Symfony requires the APP_SECRET to be set,
// but users don't care, so removing it from the
// .env files to here.
$_ENV['APP_SECRET']='not_secret_at_all';
          
require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

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
[ $messages, $error ] = SqliteHelper::doSetup();
if ($error != null) {
    echo $error;
    die();
}

/**
 * Storing update messages in the $_SERVER so I can get it in
 * templates/base.html.twig.
 *
 * I tried using getFlashBag() per
 * https://symfony.com/doc/current/controller.html, but: A) if I
 * called ->getSession()->getFlashBag() /before/ handling the request,
 * it failed because the session wasn't set; and B) if I called
 * ...->add('notice', $message) /after/ handling the request, the
 * message wouldn't show up because the request had already been
 * handled!
 *
 * I would have preferred to use $_ENV instead to store a temporary
 * message (just a hunch, no real reason I can think of), but twig
 * didn't have an _easy_ way to get env vars, but I can get server
 * vars with "app.request.server.get('varname').
 */
$_SERVER['DB_UPDATE_NOTES'] = implode(', ', $messages);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
