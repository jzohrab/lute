<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\MigrationHelper;
use App\Domain\Dictionary;

// Smoke tests
/**
 * @backupGlobals enabled
 */
final class MigrationHelper_Test extends DatabaseTestBase
{

    private string $dummydb = 'zzzz_test_setup';
    private string $oldpass;
    private string $olddb;

    public function childSetUp() {
        $this->oldpass =  $_ENV['DB_PASSWORD'];
        $this->olddb = $_ENV['DB_DATABASE'];
        DbHelpers::exec_sql('drop database if exists ' . $this->dummydb);
    }

    public function childTearDown(): void
    {
        $_ENV['DB_PASSWORD'] = $this->oldpass;
        $_ENV['DB_DATABASE'] = $this->olddb;
        DbHelpers::exec_sql('drop database if exists ' . $this->dummydb);
    }

    public function test_smoke_tests() {
        $this->assertFalse(MigrationHelper::isLuteDemo(), 'test db is not demo');
        $this->assertFalse(MigrationHelper::hasPendingMigrations(), 'everything done');
    }

    /**
     * @group demo
     */
    public function test_smoke_can_load_demo_data() {
        $dict = new Dictionary($this->term_repo);
        MigrationHelper::loadDemoData($this->language_repo, $this->book_repo, $dict);
        $this->assertTrue(true, 'dummy');
        $t = $this->text_repo->find(1);
        $this->assertEquals(explode(' ', $t->getTitle())[0], 'Tutorial', 'got tutorial, index link to /read/1 is good.');
    }

    /**
     * @group dbsetup
     */
    public function test_doSetup_new_db_valid_password() {
        $_ENV['DB_DATABASE'] = $this->dummydb;
        [ $messages, $error ] = MigrationHelper::doSetup();
        $this->assertEquals(null, $error, 'no error');
        $this->assertEquals('New database created.', $messages[0]);
        $this->assertFalse(MigrationHelper::hasPendingMigrations(), 'fully migrated');
    }

    /**
     * @group dbsetup
     */
    public function test_doSetup_new_db_bad_password() {
        $_ENV['DB_DATABASE'] = $this->dummydb;
        $_ENV['DB_PASSWORD'] = 'invalid_password';
        [ $messages, $error ] = MigrationHelper::doSetup();
        $this->assertTrue($error != null, 'got error');
        $this->assertTrue(strstr($error, 'Access denied for user') !== false, 'got access denied msg');
        $this->assertEquals(0, count($messages), 'no messages');
    }

}
