<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Text;
use App\Domain\TextStatsCache;

final class TextStatsCache_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
        $this->addTerms($this->spanish, [
            "Un gato",
            "lista",
            "tiene una"
        ]);
    }

    public function test_saving_text_loads_cache()
    {
        DbHelpers::exec_sql("delete from textstatscache");
        DbHelpers::assertRecordcountEquals("textstatscache", 0, "nothing loaded");

        $t = $this->make_text("Hola.", "Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.", $this->spanish);

        DbHelpers::assertRecordcountEquals("textstatscache", 1, "one record after save");
    }
    

    public function test_force_refresh_updates_stats()
    {
        $t = $this->make_text("Hola.", "Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.", $this->spanish);

        TextStatsCache::refresh();
        $sql = "select sUnk from textstatscache where TxID = {$t->getID()}";
        DbHelpers::assertTableContains($sql, [ "11" ], "one record after save");

        DbHelpers::exec_sql("update textstatscache set sUnk = 999");
        DbHelpers::assertTableContains($sql, [ "999" ], "updated");

        TextStatsCache::refresh();
        DbHelpers::assertTableContains($sql, [ "999" ], "refreshed, but still old value");

        TextStatsCache::force_refresh($t);
        DbHelpers::assertTableContains($sql, [ "11" ], "Updated");
    }

    /** lastmaxstatuschanged is stored in the settings table **/

    /**
     * @group faststats
     */
    public function test_refresh_sets_lastmaxstatuschanged()
    {
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        TextStatsCache::refresh();
        DbHelpers::assertRecordcountEquals("select * from settings where StKey = 'lastmaxstatuschanged'", 1, 'setting saved');
        $sql = "select * from settings where StKey = 'lastmaxstatuschanged'
          and StValue = (select convert(unix_timestamp(max(wostatuschanged)), char(40)) from words)";
        DbHelpers::assertRecordcountEquals($sql, 1, 'correct setting value saved');
    }

    /**
     * @group faststats
     */
    public function test_refresh_not_needed_if_lastmaxstatuschanged_setting_equals_max_wostatuschanged()
    {
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        TextStatsCache::refresh();

        $sql = "update settings
          set StValue = (select convert(unix_timestamp(max(wostatuschanged)), char(40)) from words)
          where StKey = 'lastmaxstatuschanged'";
        DbHelpers::exec_sql($sql);

        $this->assertFalse(TextStatsCache::needs_refresh(), "not required");

        $sql = "update settings
          set StValue = '100'
          where StKey = 'lastmaxstatuschanged'";
        DbHelpers::exec_sql($sql);
        $this->assertTrue(TextStatsCache::needs_refresh(), "required, last max changed is less than current");

        $sql = "update settings
          set StValue = (select convert(unix_timestamp(max(wostatuschanged) + 100), char(40)) from words)
          where StKey = 'lastmaxstatuschanged'";
        DbHelpers::exec_sql($sql);

        $this->assertFalse(TextStatsCache::needs_refresh(), "not required, last max is greater than current");

        DbHelpers::exec_sql('delete from words');
        $this->assertFalse(TextStatsCache::needs_refresh(), "not required, no words, no stats");
    }

    /**
     * @group faststats
     */
    public function test_refresh_needed_if_no_lastmaxstatuschanged()
    {
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        $sql = "delete from settings where StKey = 'lastmaxstatuschanged'";
        DbHelpers::exec_sql($sql);

        $this->assertTrue(TextStatsCache::needs_refresh(), "not required");
    }
}
