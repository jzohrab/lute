<?php

namespace App\Utils;

use App\Repository\SettingsRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class MysqlExportCSV {

    /**
     * Open MySQL connection using the environment settings.
     */
    private static function getConn() {
        $user = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASSWORD'];
        $host = $_ENV['DB_HOSTNAME'];
        $dbname = $_ENV['DB_DATABASE'];
        $d = "mysql:host={$host};dbname={$dbname}";

        $dbh = new \PDO($d, $user, $password);
        $dbh->query("SET NAMES 'utf8'");
        $dbh->query("SET SESSION sql_mode = ''");
        return $dbh;
    }

    private static function export_table($conn, $targetdir, $tbl) {
        $csvfile = "{$targetdir}/{$tbl}.csv";
        // echo $csvfile . "<br/>";
        $handle = fopen($csvfile, "w");
        if ($handle === false) {
            throw new \Exception("Error creating $csvfile");
        }

        $headings = [];
        $dbname = $_ENV["DB_DATABASE"];
        $sql = "SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA ='{$dbname}'
        AND TABLE_NAME ='{$tbl}'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $headings[] = $row['COLUMN_NAME'];
        }
        fputcsv($handle, $headings);

        $sql = "SELECT * FROM {$tbl}";
        // echo $sql . "<br/>";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    private static function exportWordsChecksum($conn, $targetdir) {
        $md5 = md5("START");
        $sql = "SELECT WoTextLC FROM words order by woid";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $md5 = md5($md5 . "|" . $row[0]);
        }
        $csvfile = "{$targetdir}/checksum.csv";
        file_put_contents($csvfile, $md5);
    }

    public static function doExport() {
        MysqlHelper::runMigrations(false);
        $conn = MysqlExportCSV::getConn();

        $targetdir = __DIR__ . '/../../csv_export';
        $targetdir = Path::canonicalize($targetdir);
        if (!is_dir($targetdir))
            mkdir($targetdir);

        $tables = [
            "books",
            "bookstats",
            "booktags",
            "languages",
            "sentences",
            "settings",
            "tags",
            "tags2",
            "texts",
            "texttags",
            "texttokens",
            "wordimages",
            "wordparents",
            "words",
            "wordtags"
        ];
        foreach ($tables as $t)
            MysqlExportCSV::export_table($conn, $targetdir, $t);
        MysqlExportCSV::exportWordsChecksum($conn, $targetdir);
    }

}
