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


    private function assert_setup_gives_error($expected) {
        [ $messages, $error ] = SqliteHelper::doSetup();
        $this->assertTrue($error != null, 'have error');
        $this->assertMatchesRegularExpression("/{$expected}/", $error);
    }
    
    public function test_setup_missing_DB_FILENAME_returns_error() {
        $_ENV['DB_FILENAME'] = null;
        $this->assert_setup_gives_error('Missing key DB_FILENAME');
    }

    public function test_setup_blank_DB_FILENAME_returns_error() {
        $_ENV['DB_FILENAME'] = '';
        $this->assert_setup_gives_error('Missing key DB_FILENAME');
    }

    public function test_setup_mysql_DATABASE_URL_returns_error() {
        $_ENV['DATABASE_URL'] = 'mysql:blahblah';
        $this->assert_setup_gives_error('DATABASE_URL should start with sqlite');
    }

    public function test_setup_db_created_if_not_exists() {
        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        $this->assertFalse(file_exists($f), 'no file');
        [ $messages, $error ] = SqliteHelper::doSetup();
        $this->assertTrue($error == null, 'no error');
        $this->assertMatchesRegularExpression('/New database created./', implode('; ', $messages));
        $this->assertTrue(file_exists($f), 'have file');
        $this->assertFalse(SqliteHelper::hasPendingMigrations(), 'nothing pending');
    }

    /* setup tests

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
** if not empty
*** hide "import csv" link

* manual tests
- remove the DB_FILENAME
- set DATABASE_URL to mysql
     */
}
