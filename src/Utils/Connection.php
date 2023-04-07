<?php

namespace App\Utils;

class Connection {

    /**
     * Open a new connection using the environment settings.
     */
    public static function getFromEnvironment() {
        $user = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASSWORD'];
        $host = $_ENV['DB_HOSTNAME'];
        $dbname = $_ENV['DB_DATABASE'];
        $d = "mysql:host={$host};dbname={$dbname}";

        // TODO:sqlite
        // $d = str_replace('%kernel.project_dir%', __DIR__ . '/../..', $_ENV['DATABASE_URL']);

        $dbh = new \PDO($d, $user, $password);

        $dbh->query("SET NAMES 'utf8'");
        $dbh->query("SET SESSION sql_mode = ''");
        return $dbh;
    }

}
