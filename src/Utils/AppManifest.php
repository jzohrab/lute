<?php

namespace App\Utils;

class AppManifest {

    public static function manifestPath(): string {
        return __DIR__ . '/../../manifest.json';
    }

    public static function write(): void {
        $git = function($cmd, $default = '') {
            $ret = shell_exec("git {$cmd}");
            // put in string in case it's null:
            $ret = trim("{$ret}");
            if ($ret == '')
                $ret = $default;
            return $ret;
        };

        $date = new \DateTime();
        $reldate = $date->format(DATE_RFC2822);

        $m = [
            'commit' => $git('log --pretty="%h" -n 1'),
            'tag' => $git('tag --points-at HEAD', ''),
            'release_date' => trim($reldate)
        ];

        $h = fopen(AppManifest::manifestPath(), 'w');
        fwrite($h, json_encode($m));
        fclose($h);
    }

    public static function read(): array {
        $manifest = AppManifest::manifestPath();
        $ret = [
            'commit' => null,
            'tag' => null,
            'release_date' => null
        ];
        if (file_exists($manifest)) {
            $content = file_get_contents($manifest);
            $ret = json_decode($content, true);
        }
        return $ret;
    }
}