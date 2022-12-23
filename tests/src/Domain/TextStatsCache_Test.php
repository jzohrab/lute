<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Text;
use App\Domain\TextStatsCache;

final class TextStatsCache_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        // Set up db.
        $this->load_languages();

        $lid = $this->spanish->getLgID();
        DbHelpers::add_word($lid, "Un gato", "un gato", 1, 2);
        DbHelpers::add_word($lid, "lista", "lista", 1, 1);
        DbHelpers::add_word($lid, "tiene una", "tiene una", 1, 2);

        // A parent term.
        DbHelpers::add_word($lid, "listo", "listo", 1, 1);
        DbHelpers::add_word_parent($lid, "lista", "listo");

        // Some tags for fun.
        DbHelpers::add_word_tag($lid, "Un gato", "furry");
        DbHelpers::add_word_tag($lid, "lista", "adj");
        DbHelpers::add_word_tag($lid, "lista", "another");
        DbHelpers::add_word_tag($lid, "listo", "padj1");
        DbHelpers::add_word_tag($lid, "listo", "padj2");
    }


    public function test_saving_text_loads_cache()
    {
        DbHelpers::exec_sql("delete from textstatscache");
        DbHelpers::assertRecordcountEquals("textstatscache", 0, "nothing loaded");

        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        DbHelpers::assertRecordcountEquals("textstatscache", 1, "one record after save");
    }
    

    public function test_mark_stale_and_refresh_updates_stats()
    {
        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        TextStatsCache::refresh();
        $sql = "select sUnk from textstatscache where TxID = {$t->getID()}";
        DbHelpers::assertTableContains($sql, [ "11" ], "one record after save");

        DbHelpers::exec_sql("update textstatscache set sUnk = 999");
        DbHelpers::assertTableContains($sql, [ "999" ], "updated");

        TextStatsCache::refresh();
        DbHelpers::assertTableContains($sql, [ "999" ], "refreshed, but still old value");

        TextStatsCache::markStale([ $t->getID() ]);
        TextStatsCache::refresh();
        DbHelpers::assertTableContains($sql, [ "11" ], "Updated");
    }


}
