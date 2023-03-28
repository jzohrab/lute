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
        return Connection::getFromEnvironment();
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
        return $stmt->get_result();
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
        return $stmt->insert_id;
    }

    /** Gets first field of first record. */
    private static function get_first_value($sql)
    {
        $res = DbHelpers::exec_sql_get_result($sql);
        $record = mysqli_fetch_array($res, MYSQLI_NUM);
        mysqli_free_result($res);
        $ret = null;
        if ($record) { 
            $ret = $record[0]; 
        }
        return $ret;
    }
    
    public static function ensure_using_test_db() {
        $dbname = $_ENV['DB_DATABASE'];
        $conn_db_name = DbHelpers::get_first_value("SELECT DATABASE()");

        foreach([$dbname, $conn_db_name] as $s) {
            $prefix = substr($s, 0, 5);
            if (strtolower($prefix) != 'test_') {
                $msg = "
*************************************************************
ERROR: Db name \"{$s}\" does not start with 'test_'

(Stopping tests to prevent data loss.)

Since database tests are destructive (delete/edit/change data),
you must use a dedicated test database when running tests.

1. Create a new database called 'test_<whatever_you_want>'
2. Update your env.test.local to use this new db
3. Run the tests.
*************************************************************
";
                echo $msg;
                die("Quitting");
            }
        }
    }

    public static function clean_db() {
        $tables = [
            "feedlinks",
            "languages",
            "newsfeeds",
            "sentences",
            "settings",
            "tags",
            "tags2",
            "texts",
            "texttokens",
            "texttags",

            "bookstats",
            "booktags",
            "books",

            "words",
            "wordparents",
            "wordimages",
            "wordtags"
        ];
        foreach ($tables as $t) {
            DbHelpers::exec_sql("truncate {$t}");
        }

        $alters = [
            "sentences",
            "tags",
            "texts",
            "words"
        ];
        foreach ($alters as $t) {
            DbHelpers::exec_sql("ALTER TABLE {$t} AUTO_INCREMENT = 1");
        }
    }

    /**
     * Checks.
     */

    // Test-writing-helper method, to get expected output for
    // assertTableContains.
    public static function dumpTable($sql) {
        $content = [];
        $res = DbHelpers::exec_sql_get_result($sql);
        echo "\n[\n";
        while($row = mysqli_fetch_assoc($res)) {
            $content[] = '"' . implode('; ', $row) . '"';
        }
        echo implode(",\n", $content);
        echo "\n]\n";
    }

    public static function assertTableContains($sql, $expected, $message = '') {
        $content = [];
        $res = DbHelpers::exec_sql_get_result($sql);
        while($row = mysqli_fetch_assoc($res)) {
            $rowvals = array_values($row);
            $null_to_NULL = function($v) {
                $zws = mb_chr(0x200B);
                if ($v === null)
                    return 'NULL';
                if (is_string($v) && str_contains($v, $zws))
                    return str_replace($zws, '/', $v);
                return $v;
            };
            $content[] = implode('; ', array_map($null_to_NULL, $rowvals));
        }
        mysqli_free_result($res);

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
            while($row = mysqli_fetch_assoc($res)) {
                $content[] = implode('; ', $row);
            }
            mysqli_free_result($res);
            $content = implode("\n", $content);
            $message = "{$message} ... got data:\n\n[ {$content} ]\n";
        }
        PHPUnit\Framework\Assert::assertEquals($expected, $c, $message);
    }

}

?>