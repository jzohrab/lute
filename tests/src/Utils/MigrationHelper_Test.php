<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/Parser.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\MigrationHelper;

/** Smoke tests only. */
final class MigrationHelper_Test extends DatabaseTestBase
{

    public function test_smoke_tests() {
        $this->assertFalse(MigrationHelper::isLuteDemo(), 'test db is not demo');
        $this->assertFalse(MigrationHelper::hasPendingMigrations(), 'everything done');
    }

    public function test_smoke_can_load_demo_data() {
        MigrationHelper::loadDemoData();
        $this->assertTrue(true, 'dummy');
    }

}
