<?php

namespace App\Utils;

class Backup {

    public static function createBackupUtil(): Backup {
        return new Backup();
    }

    public function __construct() {
        return $this;
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
