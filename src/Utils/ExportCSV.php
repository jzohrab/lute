<?php

namespace App\Utils;

use App\Repository\SettingsRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ExportCSV {

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
        $res = $stmt->get_result();
        while ($row = mysqli_fetch_assoc($res)) {
            $headings[] = $row['COLUMN_NAME'];
        }
        fputcsv($handle, $headings);

        $sql = "SELECT * FROM {$tbl}";
        // echo $sql . "<br/>";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
    
    public static function doExport() {
        $conn = Connection::getFromEnvironment();

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
            ExportCSV::export_table($conn, $targetdir, $t);
    }

}
