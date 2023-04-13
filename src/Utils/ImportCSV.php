<?php

namespace App\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ImportCSV {

    private static function RequiredTables() {
        return [
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
    }
    
    // ref https://gist.github.com/fcingolani/5364532
    private static function import_csv_to_sqlite(&$pdo, $csv_path, $table)
    {
        if (($csv_handle = fopen($csv_path, "r")) === FALSE)
            throw new \Exception('Cannot open CSV file');
		
        $delimiter = ',';

        $fields = fgetcsv($csv_handle, 0, $delimiter);

        $pdo->beginTransaction();
	
        $insert_fields_str = join(', ', $fields);
        $insert_values_str = join(', ', array_fill(0, count($fields),  '?'));
        $insert_sql = "INSERT INTO $table ($insert_fields_str) VALUES ($insert_values_str)";
        $insert_sth = $pdo->prepare($insert_sql);
	
        $inserted_rows = 0;
        while (($data = fgetcsv($csv_handle, 0, $delimiter)) !== FALSE) {
            $insert_sth->execute($data);
            $inserted_rows++;

            if (intval($inserted_rows / 1000) * 1000 == $inserted_rows) {
                $pdo->commit();
                $pdo->beginTransaction();
            }
        }
	
        $pdo->commit();
	
        fclose($csv_handle);
    }

    public static function MissingFiles() {
        $missing = [];
        $sourcedir = __DIR__ . '/../../data/csv_import';
        $sourcedir = Path::canonicalize($sourcedir);
        $tables = ImportCSV::RequiredTables();
        foreach ($tables as $t) {
            $csv_path = "{$sourcedir}/{$t}.csv";
            if (!file_exists($csv_path))
                $missing[] = "{$t}.csv";
        }
        return $missing;
    }

    /** Tables with data. **/
    public static function DbLoadedTables() {
        $loaded = [];
        $conn = Connection::getFromEnvironment();
        $tables = ImportCSV::RequiredTables();
        foreach ($tables as $t) {
            $sql = "select count(*) from {$t}";
            $c = $conn->query($sql)->fetch(\PDO::FETCH_NUM)[0];
            if (intval($c) > 0) {
                $loaded[] = $t;
            }
        }
        return $loaded;
    }

    private static function checkWordsChecksum($conn, $sourcedir) {
        $md5 = md5("START");
        $sql = "SELECT WoTextLC FROM words order by woid";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $md5 = md5($md5 . "|" . $row[0]);
        }
        $csvfile = "{$sourcedir}/checksum.csv";
        $expected_md5 = file_get_contents($csvfile);

        if ($md5 != $expected_md5)
            return "Warning: exported term checksum $expected_md5 doesn't match actual checksum $md5.  This could indicate an issue with the export ... the terms in your new db might not match the terms in your old db.";
        return '';
    }

    public static function doImport() {
        $conn = Connection::getFromEnvironment();
        $sourcedir = __DIR__ . '/../../data/csv_import';
        $sourcedir = Path::canonicalize($sourcedir);
        $tables = ImportCSV::RequiredTables();
        foreach ($tables as $t) {
            $csv_path = "{$sourcedir}/{$t}.csv";
            ImportCSV::import_csv_to_sqlite($conn, $csv_path, $t);
        }

        $w = ImportCSV::checkWordsChecksum($conn, $sourcedir);
        if ($w != '')
            return $w;
        return 'ok';
    }

}
