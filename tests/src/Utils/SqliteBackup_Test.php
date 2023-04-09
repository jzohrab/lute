<?php declare(strict_types=1);

use App\Utils\SqliteBackup;
use App\Repository\SettingsRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * @backupGlobals enabled
 */
final class SqliteBackup_Test extends TestCase
{

    private $config;
    private $dir;
    private $imagedir;
    private $repo;

    public function setUp(): void {
        $config = array();
        foreach (SqliteBackup::$reqkeys as $k) {
            $config[$k] = $k . '_value';
        }

        $this->dir = Path::canonicalize(__DIR__ . '/../../zz_bkp');
        $this->make_test_dir($this->dir);
        $config['BACKUP_DIR'] = $this->dir;

        $this->imagedir = Path::canonicalize(__DIR__ . '/../../zz_images');
        $this->make_test_dir($this->imagedir);
        $config['OVERRIDE_TEST_IMAGES_DIR'] = $this->imagedir;

        $this->config = $config;

        $this->repo = $this->createMock(SettingsRepository::class);
    }

    public function tearDown(): void {
        $this->rrmdir($this->imagedir);
        $this->rrmdir($this->dir);
    }
    
    private function createSqliteBackup() {
        return new SqliteBackup($this->config, $this->repo);
    }
    
    // https://stackoverflow.com/questions/1653771/how-do-i-remove-a-directory-that-is-not-empty
    private function rrmdir(string $directory)
    {
        $rd = Path::canonicalize($directory);
        // dump($directory . ' , ' . $rd);
        if (!is_dir($rd))
            return;

        // Ref https://www.php.net/manual/en/function.glob.php
        // "Include dotfiles excluding . and .. special dirs with .[!.]*"
        // Brutal.
        $pattern = $rd . '/{.[!.],}*';

        $files = glob($pattern, GLOB_BRACE);
        foreach ($files as $f) {
            if (is_dir($f)) {
                $this->rrmdir($f);
            }
            else {
                unlink($f);
            }
        }
        rmdir($rd);
    }

    private function make_test_dir($dir) {
        $this->rrmdir($dir);
        mkdir($dir);
        $this->assertEquals(0, count(glob($dir . "/*.*")), "no files");
    }


    public function test_missing_keys_all_keys_present() {
        $b = $this->createSqliteBackup();
        $this->assertTrue($b->config_keys_set(), "all keys present");
    }

    public function test_missing_keys() {
        $this->config = [];
        $b = $this->createSqliteBackup();
        $this->assertFalse($b->config_keys_set(), "not all keys present");
        $this->assertEquals($b->missing_keys(), implode(', ', SqliteBackup::$reqkeys));
    }

    public function test_one_missing_key() {
        unset($this->config['BACKUP_DIR']);
        $b = $this->createSqliteBackup();
        $this->assertFalse($b->config_keys_set(), "not all keys present");
        $this->assertEquals($b->missing_keys(), 'BACKUP_DIR');
    }

    public function test_backup_fails_if_missing_output_dir() {
        $this->config['BACKUP_DIR'] = 'some_missing_dir';
        $b = $this->createSqliteBackup();
        $this->expectException(Exception::class);
        $b->create_backup();
    }

    /**
     * @group actualbackup
     */
    public function test_backup_writes_file_to_output_dir() {
        $filesystem = new FileSystem();
        $filesystem->dumpFile($this->imagedir . '/1/file.txt', 'imagefile');

        $b = $this->createSqliteBackup();
        $b->create_backup();
        $this->assertEquals(1, count(glob($this->dir . "/*.*")), "1 file");
        $this->assertEquals(1, count(glob($this->dir . "/lute_export.db.gz")), "1 zip file");

        $this->assertEquals(1, count(glob($this->dir . "/userimages/1/*.*")), "1 file");
        $this->assertEquals(1, count(glob($this->dir . "/userimages/1/file.txt")), "correct file");
    }

    public function test_last_import_setting_is_updated_on_successful_backup() {
        $this->repo->expects($this->once())->method('saveLastBackupDatetime');
        $b = $this->createSqliteBackup();
        $b->create_backup();
    }

    public function test_should_not_run_autobackup_if_auto_is_no_or_false() {
        $this->config['BACKUP_ENABLED'] = 'yes';
        $this->config['BACKUP_AUTO'] = 'no';
        $b = $this->createSqliteBackup();
        $this->repo->expects($this->never())->method('getLastBackupDatetime');
        $this->assertFalse($b->should_run_auto_backup());
    }

    public function test_checks_if_should_run_autobackup_if_auto_is_yes_or_true() {
        $this->config['BACKUP_ENABLED'] = 'yes';
        $this->config['BACKUP_AUTO'] = 'yes';
        $b = $this->createSqliteBackup();
        $this->repo->expects($this->once())->method('getLastBackupDatetime');
        $b->should_run_auto_backup();
    }

    public function test_autobackup_returns_true_if_never_backed_up() {
        $this->config['BACKUP_ENABLED'] = 'yes';
        $this->config['BACKUP_AUTO'] = 'yes';
        $this->repo->method('getLastBackupDatetime')->willReturn(null);
        $b = $this->createSqliteBackup();
        $this->assertTrue($b->should_run_auto_backup());
    }

    public function test_autobackup_returns_true_last_backed_up_over_one_day_ago() {
        $this->config['BACKUP_ENABLED'] = 'yes';
        $this->config['BACKUP_AUTO'] = 'yes';
        $currdatetime = getdate()[0];
        $onedayago = $currdatetime - (24 * 60 * 60);

        $this->repo = $this->createMock(SettingsRepository::class);
        $this->repo->method('getLastBackupDatetime')->willReturn($onedayago - 10);
        $b = $this->createSqliteBackup();
        $this->assertTrue($b->should_run_auto_backup(), 'older than 1 day');

        $this->repo = $this->createMock(SettingsRepository::class);
        $this->repo->method('getLastBackupDatetime')->willReturn($onedayago + 10);
        $b = $this->createSqliteBackup();
        $this->assertFalse($b->should_run_auto_backup(), 'newer than 1 day');
    }

    public function test_warning_is_set_if_keys_missing() {
        $this->config = [];
        $b = $this->createSqliteBackup();
        $expected = "Missing backup environment keys in .env.local: BACKUP_DIR, BACKUP_AUTO, BACKUP_WARN";
        $this->assertEquals($b->warning(), $expected);
    }

    public function test_warn_if_last_backup_never_happened_or_is_old() {
        $currdatetime = getdate()[0];
        $oneweekago = $currdatetime - (7 * 24 * 60 * 60);

        $this->config['BACKUP_WARN'] = 'yes';
        $this->repo = $this->createMock(SettingsRepository::class);
        $this->repo->method('getLastBackupDatetime')->willReturn(null);
        $b = $this->createSqliteBackup();
        $this->assertEquals($b->warning(), 'Last backup was more than 1 week ago.', 'never backed up');

        $this->config['BACKUP_WARN'] = 'yes';
        $this->repo = $this->createMock(SettingsRepository::class);
        $this->repo->method('getLastBackupDatetime')->willReturn($oneweekago - 10);
        $b = $this->createSqliteBackup();
        $this->assertEquals($b->warning(), 'Last backup was more than 1 week ago.', 'old backup');

        $this->config['BACKUP_WARN'] = 'no';
        $this->repo = $this->createMock(SettingsRepository::class);
        $this->repo->method('getLastBackupDatetime')->willReturn($oneweekago - 10);
        $b = $this->createSqliteBackup();
        $this->assertEquals($b->warning(), '', 'no warning, turned off!');

        $this->config['BACKUP_WARN'] = 'yes';
        $this->repo = $this->createMock(SettingsRepository::class);
        $this->repo->method('getLastBackupDatetime')->willReturn($oneweekago + 10);
        $b = $this->createSqliteBackup();
        $this->assertEquals($b->warning(), '', 'No warning, backup is recent');
    }

}
