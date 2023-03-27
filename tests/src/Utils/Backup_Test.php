<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\Backup;
use PHPUnit\Framework\TestCase;

/**
 * @backupGlobals enabled
 */
final class Backup_Test extends TestCase
{

    public function test_missing_keys_all_keys_present() {
        foreach (Backup::$reqkeys as $k) {
            $_ENV[$k] = $k . '_value';
        }
        $b = new Backup($_ENV);
        $this->assertTrue($b->config_keys_set(), "all keys present");
    }

    public function test_missing_keys() {
        foreach (Backup::$reqkeys as $k) {
            $this->assertFalse(array_key_exists($k, $_ENV), "shouldn't have key " . $k);
        }
        $b = new Backup($_ENV);
        $this->assertFalse($b->config_keys_set(), "not all keys present");
        $this->assertEquals($b->missing_keys(), implode(', ', Backup::$reqkeys));
    }

    public function test_one_missing_key() {
        foreach (Backup::$reqkeys as $k) {
            $_ENV[$k] = $k . '_value';
        }
        $_ENV['BACKUP_DIR'] = null;

        $b = new Backup($_ENV);
        $this->assertFalse($b->config_keys_set(), "not all keys present");
        $this->assertEquals($b->missing_keys(), 'BACKUP_DIR');
    }

    public function test_backup_fails_if_missing_output_dir() {
        $config = [
            'BACKUP_MYSQLDUMP_COMMAND' => 'mysqldump',
            'BACKUP_DIR' => 'some_missing_dir'
        ];
        $b = new Backup($config);

        $this->expectException(Exception::class);
        $b->create_backup();
    }

    // https://stackoverflow.com/questions/1653771/how-do-i-remove-a-directory-that-is-not-empty
    private function rrmdir(string $directory)
    {
        if (!is_dir($directory))
            return;
        array_map(fn (string $file) => is_dir($file) ? $this->rrmdir($file) : unlink($file), glob($directory . '/' . '*'));

        rmdir($directory);
    }

    private function make_backup_dir() {
        $dir = __DIR__ . '/../../zz_bkp';
        $this->rrmdir($dir);
        mkdir($dir);
        $this->assertEquals(0, count(glob($dir . "/*.*")), "no files");
        return $dir;
    }

    public function test_backup_writes_file_to_output_dir() {
        $dir = $this->make_backup_dir();

        // I'm assuming that anyone running tests also has the
        // mysqldump command available!!!
        // This may not work in github actions.
        $config = [
            'BACKUP_MYSQLDUMP_COMMAND' => 'mysqldump',
            'BACKUP_DIR' => $dir
        ];
        $b = new Backup($config);
        $b->create_backup();

        $this->assertEquals(1, count(glob($dir . "/*.*")), "1 file");
        $this->assertEquals(1, count(glob($dir . "/lute_export.sql.gz")), "1 zip file");
    }

    // TOTALLY HACKY method for testing.  Backing up takes time, and I
    // don't want to actually back up during tests.  And this is
    // really just testing the testing ... lame.
    public function test_command_skip_skips_backup() {
        $dir = $this->make_backup_dir();
        $config = [
            'BACKUP_MYSQLDUMP_COMMAND' => 'skip',
            'BACKUP_DIR' => $dir
        ];
        $b = new Backup($config);
        $b->create_backup();

        $this->assertEquals(0, count(glob($dir . "/*.*")), "no files");
    }


}
