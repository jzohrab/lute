<?php

namespace App\Utils;

class Backup {

    private array $reqkeys = [
        'BACKUP_MYSQLDUMP_COMMAND',
        'BACKUP_DIR',
        'BACKUP_AUTO',
        'BACKUP_WARN'
    ];

    private array $config;
    
    /**
     * Create new Backup using environment keys as settings.
     */
    public static function createBackupUtil(): Backup {
        return new Backup();
    }

    public function __construct() {
        $this->config = array();
        foreach ($this->reqkeys as $k) {
            $this->config[$k] = $_ENV[$k];
        }
        return $this;
    }

    public function missing_keys(): string {
        $missing = [];
        foreach ($this->reqkeys as $k) {
            if (!array_key_exists($k, $this->config))
                $missing[] = $k;
            else {
                $v = $this->config[$k];
                if ($v == null || trim($v) == '')
                    $missing[] = $k;
            }
        }
        return implode(', ', $missing);
    }

    public function is_missing_keys(): bool {
        $s = $this->missing_keys();
        if ($s == null || $s == '')
            return false;
        return true;
    }
    
    public function show_warning(): bool {
        return true;
    }

    public function warning_message(): string {
        return 'TODO:BACKUP check folder';
    }

    public function create_backup(): void {

    }

}
