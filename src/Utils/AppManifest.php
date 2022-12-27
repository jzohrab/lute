<?php

namespace App\Utils;

class AppManifest {

    public static function manifestPath(): string {
        return __DIR__ . '/../../manifest.json';
    }

    public static function write(): void {

    }

    public static function read(): array {
        $ret = [
            'commit' => null,
            'tag' => null,
            'release_date' => null
        ];
        return $ret;
    }
}