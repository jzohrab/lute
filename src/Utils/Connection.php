<?php

namespace App\Utils;

class Connection {

    // Returns [ server, user, pass, dbname ]
    public static function getParams() {
        $getOrThrow = function($key, $throwIfMissing = true) {
            if (! isset($_ENV[$key]))
                throw new \Exception("Missing ENV key $key");
            $ret = $_ENV[$key];
            if ($throwIfMissing && ($ret == null || $ret == ''))
                throw new \Exception("Empty ENV key $key");
            return $ret;
        };

        return [
            $getOrThrow('DB_HOSTNAME'),
            $getOrThrow('DB_USER'),
            $getOrThrow('DB_PASSWORD', false),
            $getOrThrow('DB_DATABASE')
        ];
    }
    
    /**
     * Verify the environment connection params.
     * Throws exception if the values are no good.
     */
    public static function verifyConnectionParams()
    {
        [ $server, $userid, $passwd, $db ] = Connection::getParams();
        $conn = @mysqli_connect($server, $userid, $passwd);
        if (!$conn) {
            $errmsg = mysqli_connect_error();
            $errnum = mysqli_connect_errno();
            $msg = "{$errmsg} ({$errnum})";
            throw new \Exception($msg);
        }
        mysqli_close($conn);
    }

    public static function databaseExists() {
        $conn = null;
        try {
            $conn = @mysqli_connect(...Connection::getParams());
            mysqli_close($conn);
            return true;
        }
        catch (\Exception $e) {
            if (mysqli_connect_errno() == 1049) {
                return false;
            }

            // Otherwise, it was some other weird error ...
            $errmsg = mysqli_connect_error();
            $errnum = mysqli_connect_errno();
            $msg = "{$errmsg} ({$errnum})";
            throw new \Exception($msg);
        }
    }


    public static function createBlankDatabase() {
        [ $server, $userid, $passwd, $dbname ] = Connection::getParams();
        $conn = @mysqli_connect($server, $userid, $passwd);
        $sql = "CREATE DATABASE `{$dbname}` 
            DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
        $result = $conn->query($sql);
        mysqli_close($conn);
        if (! $result) {
            throw new \Exception("Unable to create db $dbname");
        }

        // Verify
        try {
            $conn = Connection::getFromEnvironment();
            mysqli_close($conn);
        }
        catch (\Exception $e) {
            $errmsg = mysqli_connect_error();
            $errnum = mysqli_connect_errno();
            $msg = "{$errmsg} ({$errnum})";
            throw new \Exception($msg);
        }
    }

    /**
     * Open a new connection using the environment settings.
     */
    public static function getFromEnvironment() {
        $conn = null;
        try {
            $conn = @mysqli_connect(...Connection::getParams());
        }
        catch (\Exception $e) {
            $errmsg = mysqli_connect_error();
            $errnum = mysqli_connect_errno();
            $msg = "{$errmsg} ({$errnum})";
            throw new \Exception($msg);
        }
        @mysqli_query($conn, "SET NAMES 'utf8'");
        @mysqli_query($conn, "SET SESSION sql_mode = ''");
        return $conn;
    }

}
