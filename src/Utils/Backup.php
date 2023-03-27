<?php

namespace App\Utils;

use App\Repository\SettingsRepository;

class Backup {

    public static $reqkeys = [
        'BACKUP_MYSQLDUMP_COMMAND',
        'BACKUP_DIR',
        'BACKUP_AUTO',
        'BACKUP_WARN'
    ];

    private array $config;
    private SettingsRepository $settings_repo;
    
    /**
     * Create new Backup using environment keys as settings.
     */
    public function __construct($config, SettingsRepository $settings_repo) {
        $this->config = $config;
        $this->settings_repo = $settings_repo;
        return $this;
    }

    public function missing_keys(): string {
        $missing = [];
        foreach (Backup::$reqkeys as $k) {
            $v = array_key_exists($k, $this->config) ?
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
     * @throws \Exception if the input or output file can not be opened
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
        elseif (!str_contains(strtolower($cmd), 'mysqldump')) {
            throw new \Exception("Bad BACKUP_MYSQLDUMP_COMMAND setting '{$cmd}', must contain 'mysqldump'");
        }
        else {
            $this->do_export_and_zip($cmd, $outdir);
        }

        $this->settings_repo->saveLastBackupDatetime(getdate()[0]);
        return;
    }

    private function do_export_and_zip($cmd, $outdir) {
        $backupfile = $outdir . '/lute_export.sql';

        // TODO:BACKUP get this from env.
        $dbhost = 'localhost';
        $dbuser = 'root';
        $dbpass = 'root';
        $dbname = 'test_lute';

        $fullcmd = "$cmd --complete-insert --quote-names --skip-triggers ";
        $fullcmd = $fullcmd . " --user={$dbuser} --password={$dbpass} {$dbname} > {$backupfile}";
        system($fullcmd);
        $this->gzcompressfile($backupfile);
        unlink($backupfile);
    }

    public function should_run_auto_backup(): bool {
        $setting = strtolower($this->config['BACKUP_AUTO']);
        if ($setting == 'no' || $setting == 'false')
            return false;

        $last = $this->settings_repo->getLastBackupDatetime();
        if ($last == null)
            return true;

        $curr = getdate()[0];
        $diff = $curr - $last;
        return ($diff > 24 * 60 * 60);
    }

    public function warning(): string {
        $m = $this->missing_keys();
        if ($m != null && $m != '')
            return "Missing backup environment keys in .env.local: {$m}";

        $setting = strtolower($this->config['BACKUP_WARN']);
        if ($setting == 'no' || $setting == 'false')
            return "";

        $oldbackupmsg = "Last backup was more than 1 week ago.";
        $last = $this->settings_repo->getLastBackupDatetime();
        if ($last == null)
            return $oldbackupmsg;

        $curr = getdate()[0];
        $diff = $curr - $last;
        if ($diff > 7 * 24 * 60 * 60)
            return $oldbackupmsg;

        return "";
    }
}
