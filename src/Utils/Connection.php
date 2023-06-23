<?php

namespace App\Utils;

class Connection {

    /**
     * Open a new connection using the environment settings, WITH PROPER PRAGMAS SET.
     */
    public static function getFromEnvironment() {
        // %kernel.project_dir% is a special symfony token.
        // https://symfony.com/doc/current/reference/configuration/kernel.html#project-directory
        $d = str_replace('%kernel.project_dir%', __DIR__ . '/../..', $_ENV['DATABASE_URL']);
        $dbh = new \PDO($d);

        // THIS IS EXTREMELY IMPORTANT!
        // Without this, foreign key cascade deletes do not work!
        $dbh->exec('pragma foreign_keys = ON');

        return $dbh;
    }

}
