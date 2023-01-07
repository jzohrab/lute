<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;
use App\Entity\Term;
use App\Entity\Text;
use App\Domain\Dictionary;

// Tests to validate the Doctrine mappings.
final class Dictionary_Save_Test extends DatabaseTestBase
{

    private Dictionary $dictionary;
    private TermTag $tag;
    private Term $p;
    private Term $p2;

    public function childSetUp() {
        $this->dictionary = new Dictionary($this->term_repo);
        $this->load_languages();
    }

    public function test_saving_updates_textitems2_in_same_language() {
        DbHelpers::add_textitems2($this->spanish->getLgID(), 'hoLA', 'hola', 1);
        DbHelpers::add_textitems2($this->french->getLgID(), 'HOLA', 'hola', 2);

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("HOLA");
        $t->setStatus(1);
        $t->setWordCount(1);
        $t->setTranslation('hi');
        $t->setRomanization('ho-la');
        $this->dictionary->add($t, true);

        $sql = "select Ti2WoID, Ti2LgID, Ti2Text from textitems2 order by Ti2LgID";
        $expected = [
            "{$t->getID()}; {$this->spanish->getLgID()}; hoLA",
            "0; {$this->french->getLgID()}; HOLA"
        ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");
    }


    public function test_add_updates_associated_textitems() {
        $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $this->make_text("Bonj.", "Je veux un tengo.", $this->french);

        DbHelpers::assertRecordcountEquals("textitems2", 16, 'sanity check');
        $sql = "select Ti2WoID, Ti2LgID, Ti2WordCount, Ti2Text from textitems2 where Ti2WoID <> 0 order by Ti2Order";
        DbHelpers::assertTableContains($sql, [], "No associations");

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("tengo");
        $this->dictionary->add($t, true);
        $expected = [ "{$t->getID()}; 1; 1; tengo" ];
        DbHelpers::assertTableContains($sql, $expected, "_NOW_ associated spanish text");

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("un gato");
        $this->dictionary->add($t, true);

        $expected[] = "{$t->getID()}; 1; 2; un gato";
        DbHelpers::assertTableContains($sql, $expected, "associated multi-word term");
    }


    // Production bug.
    public function test_save_multiword_term_multiple_times_is_ok() {
        $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("un gato");
        $this->dictionary->add($t, true);

        $sql = "select Ti2WoID, Ti2LgID, Ti2WordCount, Ti2Text from textitems2 where Ti2WoID <> 0 order by Ti2Order";
        $expected[] = "{$t->getID()}; 1; 2; un gato";
        DbHelpers::assertTableContains($sql, $expected, "associated multi-word term");

        // Update and resave
        $t->setStatus(5);
        $this->dictionary->add($t, true);
        DbHelpers::assertTableContains($sql, $expected, "still associated correctly");
    }

}
