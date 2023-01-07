<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;
use App\Entity\Term;
use App\Entity\Text;
use App\Domain\Dictionary;

// Tests for checking WoStatusChanged field updates.
final class Dictionary_WoStatusChanged_Test extends DatabaseTestBase
{

    private Term $term;
    private Dictionary $dictionary;

    public function childSetUp() {
        $this->dictionary = new Dictionary($this->term_repo);
        $this->load_languages();
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("PARENT");
        $t->setStatus(1);
        $t->setWordCount(1);
        $this->dictionary->add($t, true);
        $this->term = $t;
    }

    private function set_WoStatusChanged($newval) {
        $sql = "update words set WoStatusChanged = '" . $newval . "'";
        DbHelpers::exec_sql($sql);
    }

    private function get_field_value() {
        $sql = "SELECT
          WoStatusChanged,
          TIMESTAMPDIFF(SECOND, WoStatusChanged, NOW()) as diffsecs
          FROM words where WoID = {$this->term->getID()}";
        $rec = DbHelpers::exec_sql_get_result($sql);
        $a = mysqli_fetch_assoc($rec);
        $diff = intval($a['diffsecs']);
        return [ $a['WoStatusChanged'], $diff ];
    }

    private function assertUpdated($msg = '') {
        // Cleanest way to check is to timestampdiff ... can't check
        // vs current time because comparison would change with clock
        // ticks.  Yes, this is totally geeky.
        [ $val, $diff ] = $this->get_field_value();
        $msg = $msg . " Was updated (set to " . $val . ")";
        $this->assertTrue($diff < 10, $msg);
    }
    
    public function test_creating_new_term_sets_field() {
        $sql = "select WoCreated, WoStatusChanged from words
                where WoID = {$this->term->getID()}";
        $rec = DbHelpers::exec_sql_get_result($sql);
        $a = mysqli_fetch_assoc($rec);
        $this->assertEquals($a['WoCreated'], $a['WoStatusChanged']);
    }

    public function test_updating_status_updates_field() {
        $this->set_WoStatusChanged("2000-01-01 12:00:00");
        $this->term->setStatus(2);
        $this->dictionary->add($this->term, true);
        $this->assertUpdated();
    }

    public function test_setting_status_to_same_value_leaves_date() {
        $d = "2000-01-01 12:00:00";
        $this->set_WoStatusChanged($d);
        $this->term->setStatus(1);
        $this->dictionary->add($this->term, true);
        [ $val, $diff ] = $this->get_field_value();
        $this->assertEquals($val, $d, "not changed");
    }

    public function test_updating_status_via_sql_updates_field() {
        $d = "2000-01-01 12:00:00";
        DbHelpers::exec_sql("update words set WoStatusChanged = '{$d}'");
        DbHelpers::exec_sql("update words set WoStatus = 2");
        $this->assertUpdated();
    }

    public function test_setting_status_to_same_value_via_sql_no_change() {
        $d = "2000-01-01 12:00:00";
        DbHelpers::exec_sql("update words set WoStatusChanged = '{$d}'");
        DbHelpers::exec_sql("update words set WoStatus = 1");
        [ $val, $diff ] = $this->get_field_value();
        $this->assertEquals($val, $d, "not changed");
    }

}
