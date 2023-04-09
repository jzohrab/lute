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

    public function test_setup_missing_DB_FILENAME_returns_error() {
        $_ENV['DB_FILENAME'] = null;
        [ $messages, $error ] = SqliteHelper::doSetup();
        $this->assertTrue($error != null, 'have error');
        $this->assertMatchesRegularExpression('/Missing key DB_FILENAME/', $error);
    }

    /* setup tests

* IF the sqlite env var is missing, or db_filename missing, or is mysql:
** return error stop everything

* if the sqlfile doesn't exist yet:
** copy the baseline
** run any sqlite migrations needed

* if the sqlite link is demo:
** if the db is empty, load it
** show the "you're in a demo" message
** return

* if not demo:
** the sqlite db is empty:
*** show "import csv" link
*** importing: bad fields should throw an error
** if not empty
*** hide "import csv" link

* manual tests
- remove the DB_FILENAME
- set DATABASE_URL to mysql
     */
}
