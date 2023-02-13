<?php

class MysqlMigrator {
  var $dbname;
  var $conn;
  var $location;
  var $showlogging;
  var $repeatable;

  public function __construct($location, $repeatable, $host, $db, $user, $pass, $showlogging = false) {
    $this->location = $location;
    $this->repeatable = $repeatable;
    $this->showlogging = $showlogging;
    $this->dbname = $db;

    $this->conn = $this->create_connection($host, $db, $user, $pass);
    $this->create_migrations_table_if_needed();
    date_default_timezone_set('UTC');
  }

  public function __destruct()
  {
    $this->conn->close();
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
    // $this->log("mysql exec (sql: '$sql', host: '$host', db: '$db', user: '$user', pass: '$pass')");
    $this->log($sql);
    $this->exec_commands($sql);
  }

  private function log($message) {
    if ($this->showlogging) {
      echo "$message\n";
    }
  }

  private function create_connection($host, $db, $user, $pass) {
    // $this->log("connecting to db: mysqli($host, $user, $pass, $db)");
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_errno) {
        $n = $conn->connect_errno;
        $e = $conn->connect_error;
        $this->log("Failed to connect to MySQL: ({$n}) {$e}\n");
        die;
    }
    $conn->options(MYSQLI_READ_DEFAULT_GROUP,"max_allowed_packet=128M");
    return $conn;
  }

  private function process_folder() {
    $outstanding = $this->get_pending();
    $this->log("running migrations in $this->location");
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

  private function create_migrations_table_if_needed() {
    $check_sql = "SELECT TABLE_NAME FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = '{$this->dbname}' AND TABLE_NAME = '_migrations'";
    $res = $this->conn->query($check_sql);
    if ($res->num_rows != 0) {
      return;
    }
    $this->log("Creating _migrations table in database");
    if (!$this->conn->query("CREATE TABLE _migrations (filename varchar(255), PRIMARY KEY (filename))")) {
      $this->log("Table creation failed: (" . $this->conn->errno . ") " . $this->conn->error);
      die;
    }
  }

  private function should_apply($filename) {
    if (is_dir($filename)) {
      return false;
    }
    $sql = "select filename from _migrations where filename = '{$filename}'";
    $res = $this->conn->query($sql);
    return ($res->num_rows == 0);
  }

  function add_migration_to_database($file) {
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
    /* execute multi query */
    $this->conn->multi_query($commands);
    do {
      $this->conn->store_result();
      if ($this->conn->info) {
        $this->log($this->conn->info);
      }
    } while ($this->conn->next_result());

    if ($this->conn->error) {
      $this->log("error:");
      $this->log($this->conn->error);
      die;
    }
  }

}

?>