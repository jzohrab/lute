<?php

class PdoMigrator {
    var $conn;
    var $location;
    var $repeatable;
    var $showlogging;

    public function __construct($pdo, $location, $repeatable, $showlogging = false) {
        $this->conn = $pdo;
        $this->location = $location;
        $this->repeatable = $repeatable;
        $this->showlogging = $showlogging;
    }

    public function get_pending() {
        $allfiles = [];
        chdir($this->location);
        foreach (glob("*.sql") as $s)
            $allfiles[] = $s;
        foreach (glob("*.php") as $s)
            $allfiles[] = $s;
        sort($allfiles);
        $outstanding = array_filter($allfiles, fn($f) => $this->should_apply($f));
        return array_values($outstanding);
    }

    public function process() {
        $this->process_folder();
        $this->process_repeatable();
    }

    public function exec($sql) {
        $this->log($sql);
        $this->exec_commands($sql);
    }

    /////////////////////////////////

    private function log($message) {
        if ($this->showlogging) {
            echo "$message\n";
        }
    }

    private function process_folder() {
        $outstanding = $this->get_pending();
        $n = count($outstanding);
        $this->log("running $n migrations in $this->location");
        foreach ($outstanding as $file) {
            try {
                $this->process_file($file);
            }
            catch (Exception $e) {
                $msg = $e->getMessage();
                echo "\nFile {$file} exception:\n{$msg}\n";
                throw $e;
            }
            $this->add_migration_to_database($file);
        }
    }

    private function process_repeatable() {
        $folder = $this->repeatable;
        chdir($folder);
        $files = glob("*.sql");
        $n = count($files);
        $this->log("running {$n} repeatable migrations in $folder");
        foreach ($files as $file) {
            try {
                $this->process_file($file, false);
            }
            catch (Exception $e) {
                $msg = $e->getMessage();
                echo "\nFile {$file} exception:\n{$msg}\n";
                throw $e;
            }
        }
    }

    private function should_apply($filename) {
        if (is_dir($filename)) {
            return false;
        }
        $sql = "select count(filename) from _migrations where filename = '{$filename}'";
        $res = $this->conn->query($sql)->fetch(\PDO::FETCH_NUM);
        return ($res[0] == 0);
    }

    private function add_migration_to_database($file) {
        if (!$this->conn->query("INSERT INTO _migrations values ('$file')")) {
            $this->log("Table insert failed: (" . $this->conn->errno . ") " . $this->conn->error);
            die;
        }
    }

    private function process_file($file, $showmsg = true) {
        if ($showmsg) {
            $this->log("  running $file");
        }
        if (str_ends_with($file, "php")) {
            require $this->location . '/' . $file;
        }
        elseif (str_ends_with($file, "sql")) {
            $commands = file_get_contents($file);
            $this->exec_commands($commands);
        }
        else {
            throw new Exception("unknown file type for file $file");
        }
    }

    private function exec_commands($commands) {
        $stmt = $this->conn->exec($commands);
    }

}

?>