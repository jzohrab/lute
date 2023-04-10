<?php

/**
 * This file should be included at the top of any files that hit the
 * db.
 *
 * Optionally (preferably?) use DatabaseTestBase.php.
 */


use PHPUnit\Framework\TestCase;
use App\Utils\Connection;

class DbHelpers {

    private static function get_connection() {
        if (!str_contains(strtolower($_ENV['DATABASE_URL']), 'mysql')) {
            $d = str_replace('%kernel.project_dir%', __DIR__ . '/..', $_ENV['DATABASE_URL']);
            $dbh = new PDO($d);
            return $dbh;
        }

        // OLD mysql conn
        $user = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASSWORD'];
        $host = $_ENV['DB_HOSTNAME'];
        $dbname = $_ENV['DB_DATABASE'];
        $d = "mysql:host={$host};dbname={$dbname}";
        $dbh = new \PDO($d, $user, $password);
        return $dbh;
    }

    public static function exec_sql_get_result($sql, $params = null) {
        $conn = DbHelpers::get_connection();
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        if ($params) {
            $stmt->bind_param(...$params);
        }
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        return $stmt;
    }

    public static function exec_sql($sql, $params = null) {
        $conn = DbHelpers::get_connection();
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        if ($params) {
            $stmt->bind_param(...$params);
        }
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
    }

    /** Gets first field of first record. */
    private static function get_first_value($sql)
    {
        $res = DbHelpers::exec_sql_get_result($sql);
        $record = $res->fetch(\PDO::FETCH_NUM);
        $ret = null;
        if ($record) { 
            $ret = $record[0]; 
        }
        return $ret;
    }
    
    public static function ensure_using_test_db() {
        $dbname = $_ENV['DB_FILENAME'];
        $basename = basename($dbname);
        $is_test = str_contains($basename, 'test');
        if (!$is_test) {
            $msg = "
*************************************************************
ERROR: Db name \"{$basename}\" does not start with 'test'

(Stopping tests to prevent data loss.)

Since database tests are destructive (delete/edit/change data),
you must use a dedicated test database when running tests.

1. Update DB_FILENAME in your env.test.local to something like:

DB_FILENAME=%kernel.project_dir%/test_lute.db

2. Re-run the tests.  Lute will create the db if needed.
*************************************************************
";
            echo $msg;
            die("Quitting");
        }
    }

    public static function clean_db() {
        // Clean out tables in ref-integrity order.
        $tables = [
            "sentences",
            "settings",
            "texttokens",

            "booktags",
            "bookstats",

            "texttags",
            "wordtags",
            "wordparents",
            "wordimages",

            "tags",
            "tags2",
            "texts",
            "books",
            "words",
            "languages"
        ];
        foreach ($tables as $t) {
            // truncate doesn't work when referential integrity is set.
            DbHelpers::exec_sql("delete from {$t}");
        }

        /*
        $alters = [
            "sentences",
            "tags",
            "texts",
            "words"
        ];
        foreach ($alters as $t) {
            DbHelpers::exec_sql("ALTER TABLE {$t} AUTO_INCREMENT = 1");
        }
        */
    }

    /**
     * Checks.
     */

    public static function assertTableContains($sql, $expected, $message = '') {
        $content = [];
        $res = DbHelpers::exec_sql_get_result($sql);
        while($row = $res->fetch(\PDO::FETCH_NUM)) {
            $rowvals = array_values($row);
            $null_to_NULL = function($v) {
                if ($v === null)
                    return 'NULL';
                $zws = mb_chr(0x200B);
                if (is_string($v) && str_contains($v, $zws))
                    return str_replace($zws, '/', $v);
                return $v;
            };
            $content[] = implode('; ', array_map($null_to_NULL, $rowvals));
        }

        PHPUnit\Framework\Assert::assertEquals($expected, $content, $message);
    }

    /**
     * Sample calls:
     * DbHelpers::assertRecordcountEquals('select * from x where id=2', 1, 'single record');
     * DbHelpers::assertRecordcountEquals('x', 19, 'all records in table x');
     */
    public static function assertRecordcountEquals($sql, $expected, $message = '') {
        if (stripos($sql, 'select') === false) {
            $sql = "select * from {$sql}";
        }
        $c = DbHelpers::get_first_value("select count(*) as value from ({$sql}) src");

        if ($c != $expected) {
            $content = [];
            $res = DbHelpers::exec_sql_get_result($sql);
            while($row = $res->fetch(\PDO::FETCH_NUM)) {
                $content[] = implode('; ', $row);
            }
            $content = implode("\n", $content);
            $message = "{$message} ... got data:\n\n[ {$content} ]\n";
        }
        PHPUnit\Framework\Assert::assertEquals($expected, $c, $message);
    }

}

?>