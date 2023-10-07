<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Term;
use App\Entity\Text;
use App\Parse\JapaneseParser;
use App\Entity\Language;
use App\Domain\TermService;

final class TermService_findOrNew_Test extends DatabaseTestBase {

    private Term $bebida;

    public function childSetUp() {
        // set up the text
        $this->load_languages();

        $content = "Hola tengo un gato.  No TENGO una lista.  Ella tiene una bebida.";
        $t = $this->make_text("Hola.", $content, $this->spanish);

        $this->bebida = $this->addTerms($this->spanish, 'BEBIDA')[0];
    }

    public function test_load_existing_word() {
        $t = $this->term_service->findOrNew($this->spanish, 'bebida');
        $this->assertEquals($t->getID(), $this->bebida->getID(), 'id');
        $this->assertEquals($t->getText(), "BEBIDA", 'text');
    }


    public function test_load_new_word() {
        $t = $this->term_service->findOrNew($this->spanish, 'TENGO');
        $this->assertEquals($t->getID(), 0, 'new word');
        $this->assertEquals($t->getText(), "TENGO", 'text');
    }

    public function test_new_multi_word() {
        $zws = mb_chr(0x200B);
        $t = $this->term_service->findOrNew($this->spanish, "TENGO{$zws} {$zws}una");
        $this->assertEquals($t->getID(), 0, 'new word');
        $this->assertEquals($t->getText(), "TENGO{$zws} {$zws}una", 'text');
    }

    public function test_existing_multi_word() {
        $zws = mb_chr(0x200B);
        $this->addTerms($this->spanish, ["TENGO{$zws} {$zws}UNA"]);
        $t = $this->term_service->findOrNew($this->spanish, "TENGO{$zws} {$zws}una");
        $this->assertTrue($t->getID() > 0, 'maps to existing word');
        $this->assertEquals($t->getText(), "TENGO{$zws} {$zws}UNA", 'with the right text!');
    }

}
