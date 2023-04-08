<?php declare(strict_types=1);

use App\Utils\SqliteHelper;
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

}
