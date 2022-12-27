<?php

namespace App\Utils;

class AppManifest {

    public static function manifestPath(): string {
        return __DIR__ . '/../../manifest.json';
    }

    public static function write(): void {
        $commit = shell_exec('git log --pretty="%h" -n 1');
        $tag = shell_exec('git tag --points-at HEAD');

        $date = new \DateTime();
        $reldate = $date->format(DATE_RFC2822);

        $m = [
            'commit' => $commit,
            'tag' => $tag,
            'release_date' => $reldate
        ];

        $h = fopen(AppManifest::manifestPath(), 'w');
        fwrite($h, json_encode($m));
        fclose($h);
    }

    public static function read(): array {
        $manifest = AppManifest::manifestPath();
        if (! file_exists($manifest)) {
            return [
                'commit' => null,
                'tag' => null,
                'release_date' => null
            ];
        }

        if (file_exists($manifest)) {
            return json_decode(file_get_contents($manifest));
        }
    }
}