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

        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No TENGO una lista.  Ella tiene una bebida.");
        $lang = $this->spanish;
        $t->setLanguage($lang);
        $this->text_repo->save($t, true);

        $spot_check_sql = "select ti2txid, ti2woid, ti2seid, ti2order, ti2text from textitems2
where ti2order in (1, 12, 25) order by ti2order";
        $expected = [
            "1; 0; 1; 1; Hola",
            "1; 0; 2; 12; TENGO",
            "1; 0; 3; 25; bebida"
        ];
        DbHelpers::assertTableContains($spot_check_sql, $expected);

        $term = $this->addTerms($this->spanish, 'BEBIDA')[0];
        $spot_check_sql = "select ti2woid, Ti2TxID, Ti2Order, ti2seid, ti2text from textitems2
where ti2order = 25";
        $expected = [
            "{$term->getID()}; 1; 25; 3; bebida"
        ];
        DbHelpers::assertTableContains($spot_check_sql, $expected);

        $this->bebida = $term;
    }

    public function test_load_existing_wid() {
        $t = $this->reading_repo->load($this->bebida->getID(), 1, 25, '');
        $this->assertEquals($t->getID(), $this->bebida->getID(), 'id');
        $this->assertEquals($t->getText(), "BEBIDA", 'text');
    }

    public function test_no_wid_load_by_tid_and_ord_matches_existing_word() {
        $t = $this->reading_repo->load(0, 1, 25, '');
        $this->assertEquals($t->getID(), $this->bebida->getID(), 'id');
        $this->assertEquals($t->getText(), "BEBIDA", 'text');
    }


    public function test_no_wid_load_by_tid_and_ord_new_word() {
        $t = $this->reading_repo->load(0, 1, 12, '');
        $this->assertEquals($t->getID(), 0, 'new word');
        $this->assertEquals($t->getText(), "TENGO", 'text');
        $this->assertEquals($t->getLanguage()->getLgID(), $this->spanish->getLgID(), 'language set');
    }

    public function test_multi_word_overrides_tid_and_ord() {
        $t = $this->reading_repo->load(0, 1, 12, 'TENGO una');
        $this->assertEquals($t->getID(), 0, 'new word');
        $this->assertEquals($t->getText(), "TENGO una", 'text');
        $this->assertEquals($t->getLanguage()->getLgID(), $this->spanish->getLgID(), 'language set');
        $this->assertEquals($t->getStatus(), 1, 'status');
    }

    public function test_multi_word_returns_existing_word_if_it_matches_the_text() {
        $this->addTerms($this->spanish, ['TENGO UNA']);
        $t = $this->reading_repo->load(0, 1, 12, 'TENGO una');
        $this->assertTrue($t->getID() > 0, 'maps to existing word');
        $this->assertEquals($t->getText(), 'TENGO UNA', 'with the right text!');
    }


    public function test_missing_tid_or_ord_throws() {
        $msg = '';
        try { $this->reading_repo->load(0, 0, 0); }
        catch (\Exception $e) { $msg .= '1'; }
        try { $this->reading_repo->load(0, 0, 1); }
        catch (\Exception $e) { $msg .= '2'; }
        try { $this->reading_repo->load(0, 1, 0); }
        catch (\Exception $e) { $msg .= '3'; }

        try { $this->reading_repo->load(0, 1, 1); }
        catch (\Exception $e) { $msg .= 'this does not throw, the tid and ord are sufficient'; }
        try { $this->reading_repo->load(1, 1, 0); }
        catch (\Exception $e) { $msg .= 'this does not throw, the wid is sufficient'; }
        $this->assertEquals('123', $msg, 'all failed :-P');
    }


    // For non-space-delimited languages like Japanese, we need to
    // pass the count of selected tokens on the UI to the load()
    // function; we can't just rely on a simple regex to count the
    // words.
    /**
     * @group japanmultiwords
     */
    public function test_loading_with_specified_wordcount_overrides_the_calculated_wordcount() {
        if (!JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = Language::makeJapanese();
        $this->language_repo->save($japanese, true);

        $t = new Text();
        $t->setTitle("Test");
        $t->setText("私は元気です.");
        $t->setLanguage($japanese);
        $this->text_repo->save($t, true);

        $t = $this->reading_repo->load(0, $t->getID(), 3, '元気です', 2);
        $this->assertEquals($t->getID(), 0, 'new word');
        $this->assertEquals($t->getText(), '元気です', 'text');
        $this->assertEquals($t->getWordCount(), 2, 'manually set to 2 words');
    }

}
