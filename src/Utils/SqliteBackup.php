<?php

namespace App\Utils;

use App\Repository\SettingsRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class SqliteBackup {

    public static $reqkeys = [
        'BACKUP_DIR',
        'BACKUP_AUTO',
        'BACKUP_WARN'
    ];

    private array $config;
    private SettingsRepository $settings_repo;

    private ?bool $enabled;
    private bool $auto;
    private bool $warn;
    private int $keep_count = 5;
    private string $outdir;

    /**
     * Create new Backup using environment keys as settings.
     */
    public function __construct($config, SettingsRepository $settings_repo) {

        $yesortrue = function($k) use ($config) {
            if (!array_key_exists($k, $config))
                return false;
            $v = strtolower($config[$k]);
            return ($v == 'yes' || $v == 'true');
        };
        $this->enabled = $yesortrue('BACKUP_ENABLED');
        $this->auto = $yesortrue('BACKUP_AUTO');
        $this->warn = $yesortrue('BACKUP_WARN');
        if (array_key_exists('BACKUP_COUNT', $config))
            $this->keep_count = intval($config['BACKUP_COUNT']);

        $this->config = $config;
        $this->settings_repo = $settings_repo;
        return $this;
    }

    public function missing_enabled_key(): bool {
        return !array_key_exists('BACKUP_ENABLED', $this->config);
    }

    public function is_enabled(): bool {
        return $this->enabled;
    }

    public function missing_keys(): string {
        $missing = [];
        foreach (SqliteBackup::$reqkeys as $k) {
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
        if (!$this->enabled || !$this->config_keys_set() || !$this->auto)
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
            return "Missing backup environment keys in .env: {$m}";

        if (!$this->warn)
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

    private function get_outdir() {
        $outdir = $this->config['BACKUP_DIR'];
        if (!is_dir($outdir))
            throw new \Exception("Missing output directory {$outdir}");
        return $outdir;
    }

    public function create_backup(): string {
        $outdir = $this->get_outdir();
        $this->mirror_images_dir($outdir);
        return $this->create_db_backup();
    }

    /** Backup just the database, adding a suffix or datetime stamp to filename. */
    public function create_db_backup($suffix = null): string {
        $outdir = $this->get_outdir();
        if ($suffix == null)
            $suffix = date("Y-m-d_His");
        $f = $this->do_export_and_zip($suffix, $outdir);
        $this->settings_repo->saveLastBackupDatetime(getdate()[0]);
        $this->only_keep_last_N_backups($outdir);
        return $f;
    }

    private function do_export_and_zip($suffix, $outdir): string {
        $src = SqliteHelper::DbFilename();
        $backupfile = "{$outdir}/lute_backup_{$suffix}.db";
        copy($src, $backupfile);
        $f = $this->gzcompressfile($backupfile);
        unlink($backupfile);
        return $f;
    }

    private function only_keep_last_N_backups($outdir) {
        $files = glob($outdir . "/lute_backup_*.db.gz");
        rsort($files);
        $remove = array_slice($files, $this->keep_count);
        foreach ($remove as $r) {
            unlink($r);
        }
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
        $targetdir = $this->config['BACKUP_DIR'] . '/userimages_backup';
        $targetdir = Path::canonicalize($targetdir);
        if (!is_dir($targetdir))
            mkdir($targetdir);

        $sourcedir = __DIR__ . '/../../data/userimages';
        if (array_key_exists('OVERRIDE_TEST_IMAGES_DIR', $this->config))
            $sourcedir = $this->config['OVERRIDE_TEST_IMAGES_DIR'];
        $sourcedir = Path::canonicalize($sourcedir);

        $fileSystem = new FileSystem();
        $fileSystem->mirror($sourcedir, $targetdir);
    }

}
