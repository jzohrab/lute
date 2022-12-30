<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\Dictionary;
use App\Entity\Term;
use App\Entity\TermTag;

final class Dictionary_Test extends DatabaseTestBase
{

    private Dictionary $dictionary;
    private Term $p;
    private Term $p2;

    public function childSetUp(): void
    {
        $this->load_languages();

        $this->dictionary = new Dictionary(
            $this->entity_manager,
            $this->term_repo
        );

        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText("PARENT");
        $p->setStatus(1);
        $this->term_repo->save($p, true);
        $this->p = $p;

        $p2 = new Term();
        $p2->setLanguage($this->spanish);
        $p2->setText("OTHER");
        $p2->setStatus(1);
        $this->term_repo->save($p2, true);
        $this->p2 = $p2;
    }

    public function test_find_by_text_is_found()
    {
        $cases = [ 'PARENT', 'parent', 'pAReNt' ];
        foreach ($cases as $c) {
            $p = $this->dictionary->find($c, $this->spanish);
            $this->assertTrue(! is_null($p), 'parent found for case ' . $c);
            $this->assertEquals($p->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    public function test_find_by_text_not_found_returns_null()
    {
        $p = $this->dictionary->find('SOMETHING_MISSING', $this->spanish);
        $this->assertTrue($p == null, 'nothing found');
    }

    public function test_find_only_looks_in_specified_language()
    {
        $fp = new Term();
        $fp->setLanguage($this->french);
        $fp->setText('bonjour');
        $fp->setStatus(1);
        $this->term_repo->save($fp, true);

        $p = $this->dictionary->find('bonjour', $this->spanish);
        $this->assertTrue($p == null, 'french terms not checked');
    }

    public function test_findMatches_matching()
    {
        $fp = new Term();
        $fp->setLanguage($this->french);
        $fp->setText("PARENT");
        $fp->setStatus(1);
        $this->term_repo->save($fp, true);

        $cases = [ 'ARE', 'are', 'AR' ];
        foreach ($cases as $c) {
            $p = $this->dictionary->findMatches($c, $this->spanish);
            $this->assertEquals(count($p), 1, '1 match for case ' . $c . ' in spanish');
            $this->assertEquals($p[0]->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    public function test_findMatches_no_sql_injection_thanks()
    {
        $injection = "a%'; select count(*) from words;";
        $p = $this->dictionary->findMatches($injection, $this->spanish);
        $this->assertEquals(count($p), 0);
    }

    public function test_findMatches_returns_empty_if_blank_string()
    {
        $p = $this->dictionary->findMatches('', $this->spanish);
        $this->assertEquals(count($p), 0);
    }

    public function test_findMatches_returns_empty_if_different_language()
    {
        $fp = new Term();
        $fp->setLanguage($this->french);
        $fp->setText('chien');
        $fp->setStatus(1);
        $this->term_repo->save($fp, true);

        $p = $this->dictionary->findMatches('chien', $this->spanish);
        $this->assertEquals(count($p), 0, "no chien in spanish");

        $p = $this->dictionary->findMatches('chien', $this->french);
        $this->assertEquals(count($p), 1, "mais oui il y a un chien ici");
    }


    public function test_add_term_saves_term() {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('perro');
        $t->setStatus(1);
        $this->dictionary->add($t);

        $f = $this->dictionary->find('perro', $this->spanish);
        $this->assertEquals($f->getText(), 'perro', 'Term found');
        DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='perro'", 1, 'in db');
    }

    public function test_add_term_with_new_parent_text_creates_new_parent()
    {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('perros');
        $t->setParentText('perro');
        $t->addTermTag(TermTag::makeTermTag('noun'));
        $this->dictionary->add($t);

        foreach(['perros', 'perro'] as $text) {
            $f = $this->dictionary->find($text, $this->spanish);
            $this->assertEquals($f->getText(), $text, 'Term found');
            DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='{$text}'", 1, 'in db');
        }

        // Hacky sql check.
        $sql = "select w.WoText, p.WoText as ptext, tags.TgText 
            FROM words w
            INNER JOIN wordparents on WpWoID = w.WoID
            INNER JOIN words p on p.WoID = wordparents.WpParentWoID
            INNER JOIN wordtags on WtWoID = w.WoID
            INNER JOIN tags on TgID = WtTgID
            WHERE w.WoText = 'perros'";
        $exp = [ "perros; perro; noun" ];
        DbHelpers::assertTableContains($sql, $exp, "parents, tags");
    }

    public function DISABLED_test_add_term_existing_parent_creates_link() {

    }

    public function DISABLED_test_add_existing_term_throws() {

    }

    public function DISABLED_test_update_existing_term_updates() {

    }

    public function DISABLED_test_update_new_term_throws() {

    }

    public function DISABLED_test_remove_term_removes_term() {

    }

    public function DISABLED_test_remove_term_leaves_parent_breaks_wordparent_association() {

    }
    
    // TODO:move
    // search for findTermInLanguage, point to dict
    // remove TermRepository find tests
    // remove findTermInLanguage
    // remove TermRepository save ?
}
