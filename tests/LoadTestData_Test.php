<?php declare(strict_types=1);

require_once __DIR__ . '/DatabaseTestBase.php';

use App\Utils\MigrationHelper;
use App\Domain\Dictionary;

// This isn't really a test ... it just loads the database with data.
// Still reasonable to keep as a test though as it needs to always
// work.
final class LoadTestData_Test extends DatabaseTestBase
{

    /**
     * @group dev:data:clear
     */
    public function test_clear_dev_data(): void {
        // the db clear in DatabaseTestBase wipes everything.
        $this->assertEquals(1, 1, 'Dummy test so phpunit is happy :-)');
    }

    /**
     * @group dev:data:load
     */
    public function test_load_dev_data(): void
    {
        $dict = new Dictionary($this->term_repo);
        MigrationHelper::loadDemoData($this->language_repo, $this->book_repo, $dict);
        $this->assertEquals(1, 1, 'Dummy test so phpunit is happy :-)');
    }

}
