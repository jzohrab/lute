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
    public static function get_first_value($sql) 
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
            // "temptextitems",  // is dropped and re-created as needed.
            "tempwords",
            "textitems2",
            "texts",
            "textstatscache",
            "texttags",
            "tts",
            "words",
            "wordparents",
            "wordtags"
        ];
        foreach ($tables as $t) {
            DbHelpers::exec_sql("truncate {$t}");
        }

        $alters = [
            "sentences",
            "tags",
            "textitems2",
            "texts",
            "words"
        ];
        foreach ($alters as $t) {
            DbHelpers::exec_sql("ALTER TABLE {$t} AUTO_INCREMENT = 1");
        }
    }

    public static function load_language_spanish() {
        $url = "http://something.com/###";
        $sql = "INSERT INTO `languages` (`LgID`, `LgName`, `LgDict1URI`, `LgDict2URI`, `LgGoogleTranslateURI`, `LgCharacterSubstitutions`, `LgRegexpSplitSentences`, `LgExceptionsSplitSentences`, `LgRegexpWordCharacters`, `LgRemoveSpaces`, `LgSplitEachChar`, `LgRightToLeft`) VALUES (1,'Spanish','{$url}','{$url}','{$url}','´=\'|`=\'|’=\'|‘=\'|...=…|..=‥','.!?:;','Mr.|Dr.|[A-Z].|Vd.|Vds.','a-zA-ZÀ-ÖØ-öø-ȳáéíóúÁÉÍÓÚñÑ',0,0,0)";
        DbHelpers::exec_sql($sql);
    }

    /**
     * Data loaders.
     *
     * These might belong in an /api/db/ or similar.
     *
     * These are very hacky, not handling weird chars etc., and are
     * also very inefficient!  Will fix if tests get stupid slow.
     */

    public static function add_text($text, $langid, $title = 'testing') {
        $sql = "INSERT INTO texts (TxLgID, TxTitle, TxText) VALUES (?, ?, ?)";
        return DbHelpers::exec_sql($sql, ["iss", $langid, $title, $text]);
    }

    // This just hacks directly into the table, it doesn't update textitems2 etc.
    public static function add_word($WoLgID, $WoText, $WoTextLC, $WoStatus, $WoWordCount) {
        $sql = "insert into words (WoLgID, WoText, WoTextLC, WoStatus, WoWordCount) values (?, ?, ?, ?, ?);";
        $params = ["issii", $WoLgID, $WoText, $WoTextLC, $WoStatus, $WoWordCount];
        return DbHelpers::exec_sql($sql, $params);
    }

    // This just hacks directly into the table.
    public static function add_textitems2($Ti2LgID, $Ti2Text, $Ti2TextLC, $Ti2TxID= 1, $Ti2WoID = 0, $Ti2SeID = 1, $Ti2Order = 1, $Ti2WordCount = 1) {
        $sql = "insert into textitems2
          (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text, Ti2TextLC)
          values (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = ["iiiiiiss", $Ti2WoID, $Ti2LgID, $Ti2TxID, $Ti2SeID, $Ti2Order, $Ti2WordCount, $Ti2Text, $Ti2TextLC];
        return DbHelpers::exec_sql($sql, $params);
    }

    public static function add_word_parent($langid, $wordtext, $parenttext) {
        $sql = "insert into wordparents (WpWoID, WpParentWoID)
          values (
            (select WoID from words where WoText = ? and WoLgID = ?),
            (select WoID from words where WoText = ? and WoLgID = ?)
          )";
        DbHelpers::exec_sql($sql, ["sisi", $wordtext, $langid, $parenttext, $langid]);
    }

    public static function add_word_tag($langid, $wordtext, $tagtext) {
        // sql injection, who cares, it's my test.
        $sql = "insert ignore into tags(TgText, TgComment)
          values ('{$tagtext}', '{$tagtext}')";
        DbHelpers::exec_sql($sql);
        $sql = "insert ignore into wordtags (WtWoID, WtTgID) values
          ((select woid from words where wotext = '{$wordtext}' and wolgid = {$langid}),
           (select tgid from tags where tgtext='{$tagtext}'))";
        DbHelpers::exec_sql($sql);
    }

    public static function add_tags($tags) {
        $ids = [];
        foreach ($tags as $t) {
            $sql = "insert into tags (TgText, TgComment)
            values ('{$t}', '{$t} comment')";
            $id = DbHelpers::exec_sql($sql);
            $ids[] = $id;
        };
        return $ids;
    }

    public static function add_texttags($tags) {
        $ids = [];
        foreach ($tags as $t) {
            $sql = "insert into tags2 (T2Text, T2Comment)
            values ('{$t}', '{$t} comment')";
            $id = DbHelpers::exec_sql($sql);
            $ids[] = $id;
        };
        return $ids;
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
            $content[] = implode('; ', $row);
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
            $message = "{$message} ... got data:\n\n{$content}\n";
        }
        PHPUnit\Framework\Assert::assertEquals($expected, $c, $message);
    }

}

?>