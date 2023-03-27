<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\Backup;
use App\Repository\SettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * @backupGlobals enabled
 */
final class Backup_Test extends TestCase
{

    private $config;
    private $dir;

    public function setUp(): void {
        $config = array();
        foreach (Backup::$reqkeys as $k) {
            $config[$k] = $k . '_value';
        }

        // I'm assuming that anyone running tests also has the
        // mysqldump command available!!!
        // This may not work in github actions.
        $config['BACKUP_MYSQLDUMP_COMMAND'] = 'mysqldump';

        $this->dir = $this->make_backup_dir();
        $config['BACKUP_DIR'] = $this->dir;

        $this->config = $config;
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


    public function test_missing_keys_all_keys_present() {
        $b = new Backup($this->config);
        $this->assertTrue($b->config_keys_set(), "all keys present");
    }

    public function test_missing_keys() {
        $b = new Backup([]);
        $this->assertFalse($b->config_keys_set(), "not all keys present");
        $this->assertEquals($b->missing_keys(), implode(', ', Backup::$reqkeys));
    }

    public function test_one_missing_key() {
        unset($this->config['BACKUP_DIR']);
        $b = new Backup($this->config);
        $this->assertFalse($b->config_keys_set(), "not all keys present");
        $this->assertEquals($b->missing_keys(), 'BACKUP_DIR');
    }

    public function test_backup_fails_if_missing_output_dir() {
        $this->config['BACKUP_DIR'] = 'some_missing_dir';
        $b = new Backup($this->config);

        $this->expectException(Exception::class);
        $b->create_backup();
    }

    public function test_backup_writes_file_to_output_dir() {
        $b = new Backup($this->config);
        $b->create_backup();
        $this->assertEquals(1, count(glob($this->dir . "/*.*")), "1 file");
        $this->assertEquals(1, count(glob($this->dir . "/lute_export.sql.gz")), "1 zip file");
    }

    // TOTALLY HACKY method for testing.  Backing up takes time, and I
    // don't want to actually back up during tests.  And this is
    // really just testing the testing ... lame.
    public function test_command_skip_skips_backup() {
        $this->config['BACKUP_MYSQLDUMP_COMMAND'] = 'skip';
        $b = new Backup($this->config);
        $b->create_backup();

        $this->assertEquals(0, count(glob($this->dir . "/*.*")), "no files");
    }

    /**
     * @group backup
     */
    public function test_last_import_setting_is_updated_on_successful_backup() {
        $this->config['BACKUP_MYSQLDUMP_COMMAND'] = 'skip';

        $repo = $this->createMock(SettingsRepository::class);
        $repo->expects($this->once())
            ->method('saveSetting');

        $b = new Backup($this->config, $repo);
        $b->create_backup();

        $this->assertEquals(0, count(glob($this->dir . "/*.*")), "no files");
    }

}
