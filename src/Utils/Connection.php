<?php

namespace App\Utils;

class Connection {

    /**
     * Open a new connection using the environment settings.
     */
    public static function getFromEnvironment() {
        $d = str_replace('%kernel.project_dir%', __DIR__ . '/../..', $_ENV['DATABASE_URL']);
        $dbh = new \PDO($d);
        return $dbh;
    }

}
