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
    public function __construct() {
        $this->config = array();
        foreach (Backup::$reqkeys as $k) {
            $v = array_key_exists($k, $_ENV) ?
               $_ENV[$k] : null;
            $this->config[$k] = $v;
        }
        return $this;
    }

    public function missing_keys(): string {
        $missing = [];
        foreach (Backup::$reqkeys as $k) {
            $v = $this->config[$k];
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

    public function create_backup(): void {

    }

}
