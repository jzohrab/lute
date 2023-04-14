<?php declare(strict_types=1);

use App\Utils\SqliteHelper;
use App\Utils\Connection;
use PHPUnit\Framework\TestCase;

/**
 * @backupGlobals enabled
 */
final class SqliteHelper_Test extends TestCase
{

    private string $migration;

    public function setUp(): void {
        // @backupGlobals restores these during tearDown.
        $f = '%kernel.project_dir%/var/test.db';
        $_ENV['DB_FILENAME'] = $f;
        $_ENV['DATABASE_URL'] = "sqlite:///{$f}";

        $this->migration = __DIR__ . '/../../../db/migrations/1234_junk.sql';
    }

    public function tearDown(): void {
        if (file_exists($this->migration))
            unlink($this->migration);
    }

    public function test_create_smoke_test() {
        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        $this->assertFalse(file_exists($f), 'no file');
        SqliteHelper::CreateDb();
        $this->assertTrue(file_exists($f), 'have file');
        $this->assertFalse(SqliteHelper::hasPendingMigrations(), 'nothing pending');
    }

    public function test_new_migrations_are_applied_on_new_db_creation() {
        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        file_put_contents($this->migration, 'create table zzjunk (a integer)');
        SqliteHelper::CreateDb();
        $this->assertFalse(SqliteHelper::hasPendingMigrations(), 'nothing pending');
    }

    public function test_has_pending_if_new_script_found() {
        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        SqliteHelper::CreateDb();
        $this->assertFalse(SqliteHelper::hasPendingMigrations(), 'nothing pending');
        file_put_contents($this->migration, 'create table zzjunk (a integer)');
        $this->assertTrue(SqliteHelper::hasPendingMigrations(), 'has pending');
    }


    private function assert_setup_result($expected_msg, $expected_error) {
        [ $messages, $error ] = SqliteHelper::doSetup();

        if ($expected_msg != null) {
            $this->assertMatchesRegularExpression("/{$expected_msg}/", implode('; ', $messages));
        }

        if ($expected_error != null) {
            $this->assertTrue($error != null, 'have error');
            $this->assertMatchesRegularExpression("/{$expected_error}/", $error);
        }
        else {
            $this->assertTrue($error == null, 'no error');
        }
    }
    
    public function test_setup_missing_DB_FILENAME_returns_error() {
        $_ENV['DB_FILENAME'] = null;
        $this->assert_setup_result(null, 'Missing key DB_FILENAME');
    }

    public function test_setup_blank_DB_FILENAME_returns_error() {
        $_ENV['DB_FILENAME'] = '';
        $this->assert_setup_result(null, 'Missing key DB_FILENAME');
    }

    public function test_setup_non_sqlite_DATABASE_URL_returns_error() {
        $_ENV['DATABASE_URL'] = 'something:blahblah';
        $this->assert_setup_result(null, 'DATABASE_URL should start with sqlite');
    }

    public function test_setup_db_created_if_not_exists() {
        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        $this->assertFalse(file_exists($f), 'no file');
        $this->assert_setup_result("New database created.", null);
        $this->assertTrue(file_exists($f), 'have file');
        $this->assertFalse(SqliteHelper::hasPendingMigrations(), 'nothing pending');
    }

    public function test_setup_loads_demo_db() {
        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        $this->assertFalse(file_exists($f), 'no file');

        $this->assert_setup_result("New database created.", null);
        $this->assertTrue(file_exists($f), 'have file');

        $this->assertTrue(SqliteHelper::isDemoData(), 'demo loaded');
        $this->assertFalse(SqliteHelper::dbIsEmpty(), 'not empty');
    }

    public function test_wiping_data_resets_to_empty() {
        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        $this->assertFalse(file_exists($f), 'no file');

        $this->assert_setup_result("New database created.", null);
        $this->assertTrue(file_exists($f), 'have file');

        $this->assertTrue(SqliteHelper::isDemoData(), 'demo loaded');
        $this->assertFalse(SqliteHelper::dbIsEmpty(), 'not empty');

        SqliteHelper::clearDb();
        $this->assertFalse(SqliteHelper::isDemoData(), 'demo not loaded');
        $this->assertTrue(SqliteHelper::dbIsEmpty(), 'is empty');

    }

    // On load of new, "show_wipe_db_link" is true
    // after wipe, "show_wipe_db_link" is false
}
