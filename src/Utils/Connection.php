<?php

namespace App\Utils;

class Connection {

    public static function getFromEnvironment() {

        $getOrThrow = function($key) {
            if (! isset($_ENV[$key]))
                throw new \Exception("Missing ENV key $key");
            $ret = $_ENV[$key];
            if ($ret == null || $ret == '')
                throw new \Exception("Empty ENV key $key");
            return $ret;
        };

        $server = $getOrThrow('DB_HOSTNAME');
        $userid = $getOrThrow('DB_USER');
        $passwd = $getOrThrow('DB_PASSWORD');
        $dbname = $getOrThrow('DB_DATABASE');

        $conn = @mysqli_connect($server, $userid, $passwd, $dbname);
        @mysqli_query($conn, "SET SESSION sql_mode = ''");
        return $conn;
    }

}
