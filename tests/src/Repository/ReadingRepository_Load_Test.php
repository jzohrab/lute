<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Term;
use App\Entity\Text;
use App\Domain\JapaneseParser;
use App\Entity\Language;
use App\Repository\TextItemRepository;

final class ReadingRepository_Load_Test extends DatabaseTestBase {

    private Term $bebida;

    public function childSetUp() {
        // set up the text
        $this->load_languages();

        $content = "Hola tengo un gato.  No TENGO una lista.  Ella tiene una bebida.";
        $t = $this->make_text("Hola.", $content, $this->spanish);

        $this->bebida = $this->addTerms($this->spanish, 'BEBIDA')[0];
    }

    public function test_load_existing_wid() {
        $t = $this->reading_repo->load($this->bebida->getID(), 1, 25, 'bebida');
        $this->assertEquals($t->getID(), $this->bebida->getID(), 'id');
        $this->assertEquals($t->getText(), "BEBIDA", 'text');
    }

    public function test_no_wid_load_by_tid_and_ord_matches_existing_word() {
        $t = $this->reading_repo->load(0, 1, 25, 'bebida');
        $this->assertEquals($t->getID(), $this->bebida->getID(), 'id');
        $this->assertEquals($t->getText(), "BEBIDA", 'text');
    }


    public function test_no_wid_load_by_tid_and_ord_new_word() {
        $t = $this->reading_repo->load(0, 1, 12, 'TENGO');
        $this->assertEquals($t->getID(), 0, 'new word');
        $this->assertEquals($t->getText(), "TENGO", 'text');
        $this->assertEquals($t->getLanguage()->getLgID(), $this->spanish->getLgID(), 'language set');
    }

    public function test_multi_word_overrides_tid_and_ord() {
        $zws = mb_chr(0x200B);
        $t = $this->reading_repo->load(0, 1, 12, "TENGO{$zws} {$zws}una");
        $this->assertEquals($t->getID(), 0, 'new word');
        $this->assertEquals($t->getText(), "TENGO{$zws} {$zws}una", 'text');
        $this->assertEquals($t->getLanguage()->getLgID(), $this->spanish->getLgID(), 'language set');
        $this->assertEquals($t->getStatus(), 1, 'status');
    }

    public function test_multi_word_returns_existing_word_if_it_matches_the_text() {
        $zws = mb_chr(0x200B);
        $this->addTerms($this->spanish, ["TENGO{$zws} {$zws}UNA"]);
        $t = $this->reading_repo->load(0, 1, 12, "TENGO{$zws} {$zws}una");
        $this->assertTrue($t->getID() > 0, 'maps to existing word');
        $this->assertEquals($t->getText(), "TENGO{$zws} {$zws}UNA", 'with the right text!');
    }


    public function test_missing_tid_or_wid_throws() {
        $this->markTestSkipped('skipping for now, need to change lute.js');
        $msg = '';
        try { $this->reading_repo->load(0, 0, 0); }
        catch (\Exception $e) { $msg .= '1'; }
        try { $this->reading_repo->load(0, 0, 1); }
        catch (\Exception $e) { $msg .= '2'; }
        try { $this->reading_repo->load(0, 1, 0); }
        catch (\Exception $e) { $msg .= '3'; }
        try { $this->reading_repo->load(0, 1, 1); }
        catch (\Exception $e) { $msg .= '4'; }
        $this->assertEquals('1234', $msg, 'all failed :-P');

        try { $this->reading_repo->load(1, 1, 0); }
        catch (\Exception $e) { $msg .= 'this does not throw, the wid is sufficient'; }
    }

}
