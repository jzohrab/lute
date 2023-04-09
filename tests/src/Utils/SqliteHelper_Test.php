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

    public function test_setup_mysql_DATABASE_URL_returns_error() {
        $_ENV['DATABASE_URL'] = 'mysql:blahblah';
        $this->assert_setup_result(null, 'DATABASE_URL should start with sqlite');
    }

    public function test_setup_db_created_if_not_exists() {
        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        $this->assertFalse(file_exists($f), 'no file');
        $this->assertFalse(SqliteHelper::isDemoDb(), "not demo db");
        $this->assert_setup_result("New database created.", null);
        $this->assertTrue(file_exists($f), 'have file');
        $this->assertFalse(SqliteHelper::hasPendingMigrations(), 'nothing pending');

        $this->assertFalse(SqliteHelper::isEmptyDemo(), 'NOT empty demo');
        $pdo = Connection::getFromEnvironment();
        $sql = "select count(*) from books";
        $res = $pdo->query($sql)->fetch(\PDO::FETCH_NUM);
        $this->assertEquals(0, intval($res[0]), 'no data loaded');
    }

    public function test_setup_demo_db() {
        $f = '%kernel.project_dir%/var/lute_demo.db';
        $_ENV['DB_FILENAME'] = $f;
        $_ENV['DATABASE_URL'] = "sqlite:///{$f}";

        $f = SqliteHelper::DbFilename();
        if (file_exists($f))
            unlink($f);
        $this->assertFalse(file_exists($f), 'no file');
        $this->assertTrue(SqliteHelper::isDemoDb(), "is demo db");

        $this->assert_setup_result("New database created.", null);
        $this->assertTrue(file_exists($f), 'have file');

        $this->assertTrue(SqliteHelper::isEmptyDemo(), 'empty demo');
        $pdo = Connection::getFromEnvironment();
        $sql = "select count(*) from books";
        $res = $pdo->query($sql)->fetch(\PDO::FETCH_NUM);
        $this->assertEquals(0, intval($res[0]), 'no demo loaded');
    }

}
