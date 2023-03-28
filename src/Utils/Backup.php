<?php

namespace App\Utils;

use App\Repository\SettingsRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

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

    public function missing_enabled_key(): bool {
        return !array_key_exists('BACKUP_ENABLED', $this->config);
    }

    public function is_enabled(): bool {
        if ($this->missing_enabled_key())
            return false;
        $k = strtolower($this->config['BACKUP_ENABLED']);
        return ($k == 'yes' || $k == 'true');
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

    public function should_run_auto_backup(): bool {
        if (!$this->is_enabled())
            return false;
        if (!$this->config_keys_set())
            return false;
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

    public function create_backup(): string {
        $outdir = $this->config['BACKUP_DIR'];
        if (!is_dir($outdir))
            throw new \Exception("Missing output directory {$outdir}");

        $this->mirror_images_dir($outdir);

        $f = "zippedfilename";
        $cmd = $this->config['BACKUP_MYSQLDUMP_COMMAND'];
        if (strtolower($cmd) == 'skip') {
            // do nothing
        }
        elseif (!str_contains(strtolower($cmd), 'mysqldump')) {
            throw new \Exception("Bad BACKUP_MYSQLDUMP_COMMAND setting '{$cmd}', must contain 'mysqldump'");
        }
        else {
            $f = $this->do_export_and_zip($cmd, $outdir);
        }

        $this->settings_repo->saveLastBackupDatetime(getdate()[0]);
        return $f;
    }

    private function do_export_and_zip($cmd, $outdir): string {
        $backupfile = $outdir . '/lute_export.sql';

        $dbhost = $_ENV['DB_HOSTNAME'];
        $dbuser = $_ENV['DB_USER'];
        $dbpass = $_ENV['DB_PASSWORD'];
        $dbname = $_ENV['DB_DATABASE'];

        $fullcmd = "$cmd --complete-insert --quote-names --skip-triggers ";
        $fullcmd = $fullcmd . " --user={$dbuser} --password={$dbpass} {$dbname} > {$backupfile}";
        $ret = system($fullcmd, $resultcode);

        if ($resultcode != 0) {
            $msg = [
                "Backup command failed with error code {$resultcode}.",
                "",
                "Command:",
                $fullcmd,
                "",
                "Please check your BACKUP_MYSQLDUMP_COMMAND config setting."
            ];
            throw new \Exception(implode("__BREAK__", $msg));
        }

        $f = $this->gzcompressfile($backupfile);
        unlink($backupfile);

        return $f;
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

    private function mirror_images_dir() {
        $targetdir = $this->config['BACKUP_DIR'] . '/userimages';
        $targetdir = Path::canonicalize($targetdir);
        if (!is_dir($targetdir))
            mkdir($targetdir);

        $sourcedir = __DIR__ . '/../../public/userimages';
        if (array_key_exists('OVERRIDE_TEST_IMAGES_DIR', $this->config))
            $sourcedir = $this->config['OVERRIDE_TEST_IMAGES_DIR'];
        $sourcedir = Path::canonicalize($sourcedir);

        $fileSystem = new FileSystem();
        $fileSystem->mirror($sourcedir, $targetdir);
    }

}
