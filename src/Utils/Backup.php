<?php

namespace App\Utils;

class Backup {

    public static $reqkeys = [
        'BACKUP_MYSQLDUMP_COMMAND',
        'BACKUP_DIR',
        'BACKUP_AUTO',
        'BACKUP_WARN'
    ];

    private array $config;
    
    /**
     * Create new Backup using environment keys as settings.
     */
    public function __construct($config) {
        $this->config = $config;
        return $this;
    }

    public function missing_keys(): string {
        $missing = [];
        foreach (Backup::$reqkeys as $k) {
            $v = array_key_exists($k, $_ENV) ?
               $this->config[$k] : null;
            if ($v == null || trim($v) == '')
                $missing[] = $k;
        }
        return implode(', ', $missing);
    }

    public function config_keys_set(): bool {
        $s = $this->missing_keys();
        if ($s == null || $s == '')
            return true;
        return false;
    }
    
    public function show_warning(): bool {
        return true;
    }

    public function warning_message(): string {
        return 'TODO:BACKUP check folder';
    }

    // https://stackoverflow.com/questions/6073397/
    // how-do-you-create-a-gz-file-using-php/56140427#56140427
    /**
     * Compress a file using gzip
     *
     * Rewritten from Simon East's version here:
     * https://stackoverflow.com/a/22754032/3499843
     *
     * @param string $inFilename Input filename
     * @param int    $level      Compression level (default: 9)
     *
     * @throws Exception if the input or output file can not be opened
     *
     * @return string Output filename
     */
    function gzcompressfile(string $inFilename, int $level = 9): string
    {
        // Is the file gzipped already?
        $extension = pathinfo($inFilename, PATHINFO_EXTENSION);
        if ($extension == "gz") {
            return $inFilename;
        }

        // Open input file
        $inFile = fopen($inFilename, "rb");
        if ($inFile === false) {
            throw new \Exception("Unable to open input file: $inFilename");
        }

        // Open output file
        $gzFilename = $inFilename.".gz";
        $mode = "wb".$level;
        $gzFile = gzopen($gzFilename, $mode);
        if ($gzFile === false) {
            fclose($inFile);
            throw new \Exception("Unable to open output file: $gzFilename");
        }

        // Stream copy
        $length = 512 * 1024; // 512 kB
        while (!feof($inFile)) {
            gzwrite($gzFile, fread($inFile, $length));
        }

        // Close files
        fclose($inFile);
        gzclose($gzFile);

        return $gzFilename;
    }


    public function create_backup(): void {
        $outdir = $this->config['BACKUP_DIR'];
        if (!is_dir($outdir))
            throw new \Exception("Missing output directory {$outdir}");

        $cmd = $this->config['BACKUP_MYSQLDUMP_COMMAND'];
        if (strtolower($cmd) == 'skip') {
            // do nothing
        }
        else {
            $this->do_export_and_zip($cmd, $outdir);
        }
        return;
    }

    private function do_export_and_zip($cmd, $outdir) {
        $backupfile = $outdir . '/lute_export.sql';

        // TODO:BACKUP get this from env.
        $dbhost = 'localhost';
        $dbuser = 'root';
        $dbpass = 'root';
        $dbname = 'test_lute';

        system("$cmd -h $dbhost -u $dbuser -p$dbpass $dbname > $backupfile");
        $this->gzcompressfile($backupfile);
        unlink($backupfile);
    }

}
