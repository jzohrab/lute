<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/Parser.php';
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

    public function childSetUp() {
        DbHelpers::exec_sql('drop database if exists ' . $this->dummydb);
    }

    public function childTearDown(): void
    {
        DbHelpers::exec_sql('drop database if exists ' . $this->dummydb);
    }

    public function test_smoke_tests() {
        $this->assertFalse(MigrationHelper::isLuteDemo(), 'test db is not demo');
        $this->assertFalse(MigrationHelper::isLuteTest(), 'test db is test!');
        $this->assertFalse(MigrationHelper::hasPendingMigrations(), 'everything done');
    }

    /**
     * @group demo
     */
    public function test_smoke_can_load_demo_data() {
        $dict = new Dictionary($this->entity_manager);
        MigrationHelper::loadDemoData($this->language_repo, $this->text_repo, $dict);
        $this->assertTrue(true, 'dummy');
        $t = $this->text_repo->find(1);
        $this->assertEquals($t->getTitle(), 'Tutorial', 'got tutorial, index link to /read/1 is good.');
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
}
