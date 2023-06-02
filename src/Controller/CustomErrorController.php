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
                'SERVER[DATABASE_URL]',
                'SERVER[REDIRECT_STATUS]',
                'SERVER[HTTP_USER_AGENT]',
                'SERVER[CONTENT_TYPE]',
                'SERVER[HTTP_REFERER]',
                'SERVER[SERVER_SOFTWARE]',
                'SERVER[REDIRECT_URL]',
                'SERVER[REQUEST_METHOD]',
                'SERVER[REQUEST_URI]',
                'SERVER[DB_HOSTNAME]',
                'SERVER[DB_USER]',
                'SERVER[DB_PASSWORD]',
                'SERVER[DB_DATABASE]',
                'ENV[APP_ENV]'
            );
            return in_array($key, $keys);
        };

        $allkeys = [];
        foreach(getenv() as $k => $v) {
            $allkeys["getenv()[$k]"] = $v;
        };
        foreach($_ENV as $k => $v) {
            $allkeys["ENV[$k]"] = $v;
        };
        foreach($_SERVER as $k => $v) {
            $allkeys["SERVER[$k]"] = $v;
        };

        $allkeys = array_filter($allkeys, $filterkeys, ARRAY_FILTER_USE_KEY);

        $data = [
            'Error message' => $exception->getMessage(),
            'stack trace' => $exception->getTraceAsString(),
            'status code' => $exception->getStatusCode(),
            'status text' => $exception->getStatusText(),

            'Lute version tag' => $gittag,
            'Lute commit' => $commit,
            'Lute version release date' => $releasedate,
        ];

        $alldata = array_merge($data, $allkeys);
        $alldata['isdev'] = ($_ENV['APP_ENV'] == 'dev');
        
        return $this->render('bundles/TwigBundle/Exception/error.html.twig', [
            'data' => $alldata
        ]);
    }
}