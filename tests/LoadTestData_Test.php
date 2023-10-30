<?php declare(strict_types=1);

require_once __DIR__ . '/DatabaseTestBase.php';

use App\Utils\DemoDataLoader;
use App\Domain\TermService;
use App\Utils\SqliteHelper;

// This isn't really a test ... it just loads the database with data.
// Still reasonable to keep as a test though as it needs to always
// work.
final class LoadTestData_Test extends DatabaseTestBase
{

    /**
     * @group dev:data
     * @group dev:data:clear
     */
    public function test_clear_dev_data(): void {  // V3-port: DONE - never used, really.
        // the db clear in DatabaseTestBase wipes everything.
        $this->assertEquals(1, 1, 'Dummy test so phpunit is happy :-)');
    }

    /**
     * @group dev:data
     * @group dev:data:load
     */
    public function test_load_dev_data(): void  // V3-port: DONE - skipping for now
    {
        $term_svc = new TermService($this->term_repo);
        DemoDataLoader::loadDemoData($this->language_repo, $this->book_repo, $term_svc);
        $sql = "select * from settings where StKey = 'IsDemoData'";
        DbHelpers::assertRecordcountEquals($sql, 1, "key set");
    }


    /**
     * @group dev:data
     */
    public function test_wipe_db_only_works_if_flag_is_set(): void  // V3-port: DONE - in db/demo.py
    {
        $term_svc = new TermService($this->term_repo);
        DemoDataLoader::loadDemoData($this->language_repo, $this->book_repo, $term_svc);
        $sql = "select * from settings where StKey = 'IsDemoData'";
        DbHelpers::assertRecordcountEquals($sql, 1, "key set");
        $this->assertTrue(SqliteHelper::isDemoData(), "flag set");

        SqliteHelper::clearDb();
        DbHelpers::assertRecordcountEquals($sql, 0, "key removed");

        $this->assertFalse(SqliteHelper::isDemoData(), "no longer the demo");

        $this->expectException(\Exception::class);
        SqliteHelper::clearDb();

        $sql = "insert into settings (StKey, StValue) values ('IsDemoData', 1)";
        $this->assertTrue(SqliteHelper::isDemoData(), "is the demo, even though it's empty");
        DbHelpers::exec_sql($sql);
        SqliteHelper::clearDb();  // ok.
    }

    /**
     * @group dev:data
     */
    public function test_is_demo_if_flag_set(): void  // V3-port: DONE - in db/demo.py
    {
        $term_svc = new TermService($this->term_repo);
        DemoDataLoader::loadDemoData($this->language_repo, $this->book_repo, $term_svc);
        $this->assertTrue(SqliteHelper::isDemoData(), "flag set");

        SqliteHelper::clearDb();

        $this->assertFalse(SqliteHelper::isDemoData(), "no longer the demo");

        $sql = "insert into settings (StKey, StValue) values ('IsDemoData', 1)";
        DbHelpers::exec_sql($sql);
        $this->assertTrue(SqliteHelper::isDemoData(), "flag set again");
    }
    
}
