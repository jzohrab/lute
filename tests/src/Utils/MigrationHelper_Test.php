<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/Parser.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\MigrationHelper;
use App\Domain\Dictionary;

/** Smoke tests only. */
final class MigrationHelper_Test extends DatabaseTestBase
{

    public function test_smoke_tests() {
        $this->assertFalse(MigrationHelper::isLuteDemo(), 'test db is not demo');
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

}
