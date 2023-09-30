<?php

namespace App\Utils;

class Connection {

    /**
     * Open a new connection using the environment settings, WITH PROPER PRAGMAS SET.
     */
    public static function getFromEnvironment() {
        // %kernel.project_dir% is a special symfony token.
        // https://symfony.com/doc/current/reference/configuration/kernel.html#project-directory
        // Replace it with the real path.
        // The first two {$ds}.. are to get the correct relative path,
        // and the final {$ds} replaces the '/' after %kernel.project_dir%.
        $ds = DIRECTORY_SEPARATOR;
        $replacement = __DIR__ . "{$ds}..{$ds}..{$ds}";

        $d = str_replace('%kernel.project_dir%/', $replacement, $_ENV['DB_FILENAME']);

        $dburl = "sqlite:///" . $d;
        if (array_key_exists('DB_WINDOWS_REPLACE_TRIPLE_SLASH', $_ENV)) {
            $replace = $_ENV['DB_WINDOWS_REPLACE_TRIPLE_SLASH'];
            $replace = strtolower($replace);
            if ($replace == 'yes') {
                $dburl = str_replace('sqlite:///','sqlite:',$dburl);
            }
        }
        dump('DB URL: ' . $dburl);  // TODO:remove
        $dbh = new \PDO($dburl);

        // THIS IS EXTREMELY IMPORTANT!
        // Without this, foreign key cascade deletes do not work!
        $dbh->exec('pragma foreign_keys = ON');

        return $dbh;
    }

}
