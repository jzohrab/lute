<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;
use App\Entity\Term;
use App\Entity\Language;
use App\Parse\JapaneseParser;
use App\Entity\Text;

// Tests to validate the Doctrine mappings.
final class TermService_Save_Test extends DatabaseTestBase
{

    private TermTag $tag;
    private Term $p;
    private Term $p2;

    public function childSetUp() {
        $this->load_languages();
    }


    public function test_add_updates_associated_textitems() {
        $st = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $ft = $this->make_text("Bonj.", "Je veux un tengo.", $this->french);

        $t = new Term($this->spanish, "tengo");
        $this->term_service->add($t, true);

        $this->assert_rendered_text_equals($st, "Hola/ /tengo(1)/ /un/ /gato/.");
        $this->assert_rendered_text_equals($ft, "Je/ /veux/ /un/ /tengo/.");

        $t = new Term($this->spanish, "un gato");
        $this->term_service->add($t, true);
        $this->assert_rendered_text_equals($st, "Hola/ /tengo(1)/ /un gato(1)/.");
    }


    public function test_textitems_not_associated_until_flush() {
        $st = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        $t1 = new Term($this->spanish, "tengo");
        $t2 = new Term($this->spanish, "un gato");

        $this->term_service->add($t1, false);
        $this->term_service->add($t2, false);

        $this->assert_rendered_text_equals($st, "Hola/ /tengo/ /un/ /gato/.");

        $this->term_service->flush();
        $this->assert_rendered_text_equals($st, "Hola/ /tengo(1)/ /un gato(1)/.");
    }


    /**
     * @group mwordparent
     */
    public function test_multiword_parent_item_associated() {
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        $t1 = new Term($this->spanish, "tengo");
        $t2 = new Term($this->spanish, "un gato");
        $t1->addParent($t2);

        $this->assert_rendered_text_equals($t, "Hola/ /tengo/ /un/ /gato/.");

        $this->term_service->add($t1, false);

        $this->term_service->flush();
        $this->assert_rendered_text_equals($t, "Hola/ /tengo(1)/ /un gato(1)/.");
    }


    /**
     * @group zws
     */
    public function test_textitems_un_associated_after_remove() {
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $this->make_text("Bonj.", "Je veux un tengo.", $this->french);

        $this->assert_rendered_text_equals($t, "Hola/ /tengo/ /un/ /gato/.");

        $t1 = new Term($this->spanish, "tengo");
        $t2 = new Term($this->spanish, "un gato");
        $this->term_service->add($t1, false);
        $this->term_service->add($t2, false);
        $this->term_service->flush();

        $this->assert_rendered_text_equals($t, "Hola/ /tengo(1)/ /un gato(1)/.");

        $this->term_service->remove($t1, false);
        $this->term_service->remove($t2, false);
        $this->term_service->flush();
        $this->assert_rendered_text_equals($t, "Hola/ /tengo/ /un/ /gato/.");
    }


    // Production bug.
    public function test_save_multiword_term_multiple_times_is_ok() {
        $text = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("un gato");
        $this->term_service->add($t, true);
        $this->assert_rendered_text_equals($text, "Hola/ /tengo/ /un gato(1)/.");

        // Update and resave
        $t->setStatus(5);
        $this->term_service->add($t, true);
        $this->assert_rendered_text_equals($text, "Hola/ /tengo/ /un gato(5)/.");
    }


    /**
     * @group japanesemultiword
     */
    public function test_save_japanese_multiword_updates_textitems() {
        if (!JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = Language::makeJapanese();
        $this->language_repo->save($japanese, true);

        $text = $this->make_text("Hi", "私は元気です.", $japanese);
        $this->assert_rendered_text_equals($text, "私/は/元気/です/.");
        
        $term = new Term($japanese, "元気です");
        $this->term_service->add($term, true);

        $this->assert_rendered_text_equals($text, "私/は/元気です(1)/.");
    }

    /**
     * @group termflashremoval_2
     */
    public function test_term_flash_can_be_removed() {
        $p = new Term($this->spanish, 'perro');
        $p->setFlashMessage('hola');
        $this->term_service->add($p, true);

        $sql = "select WfWoID, WfMessage from wordflashmessages";
        $expected = [ "{$p->getID()}; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");

        $pfind = $this->term_repo->find($p->getID());
        $this->assertEquals($pfind->popFlashMessage(), "hola", "message popped");
        $this->term_service->add($pfind, true);
        $expected = [];
        DbHelpers::assertTableContains($sql, $expected, "removed");
    }

}
