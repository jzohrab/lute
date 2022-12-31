<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use App\Utils\AppManifest;

class CustomErrorController extends AbstractController
{

    public function show(FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $m = AppManifest::read();
        $commit = $m['commit'];
        $gittag = $m['tag'];
        $releasedate = $m['release_date'];

        $filterkeys = function($key) {
            $keys = array(
                'DATABASE_URL',
                'REDIRECT_STATUS',
                'HTTP_USER_AGENT',
                'CONTENT_TYPE',
                'HTTP_REFERER',
                'SERVER_SOFTWARE',
                'REDIRECT_URL',
                'REQUEST_METHOD',
                'REQUEST_URI',
                'DB_HOSTNAME',
                'DB_USER',
                'DB_PASSWORD',
                'DB_DATABASE',
                'APP_ENV'
            );
            return in_array($key, $keys);
        };

        $allkeys = [];
        $allenv = array_filter(getenv(), $filterkeys, ARRAY_FILTER_USE_KEY);
        foreach($allenv as $k => $v) {
            $allkeys["getenv()[$k]"] = $v;
        };
        $env = array_filter($_ENV, $filterkeys, ARRAY_FILTER_USE_KEY);
        foreach($env as $k => $v) {
            $allkeys["ENV[$k]"] = $v;
        };
        $server = array_filter($_SERVER, $filterkeys, ARRAY_FILTER_USE_KEY);
        foreach($server as $k => $v) {
            $allkeys["SERVER[$k]"] = $v;
        };

        return $this->render('bundles/TwigBundle/Exception/error.html.twig', [
            "status_code" => $exception->getStatusCode(),
            "status_text" => $exception->getStatusText(),
            'exception' => $exception,

            'tag' => $gittag,
            'commit' => $commit,
            'release_date' => $releasedate,

            'isdev' => ($_ENV['APP_ENV'] == 'dev'),
            'env_vars' => $allkeys
        ]);
    }
}