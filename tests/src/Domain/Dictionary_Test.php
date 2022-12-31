<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\Dictionary;
use App\Entity\Term;
use App\Entity\Text;
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
    }

    public function test_find_by_text_is_found()
    {
        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText("PARENT");
        $p->setStatus(1);
        $this->term_repo->save($p, true);
        $this->p = $p;

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
        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText("PARENT");
        $p->setStatus(1);
        $this->term_repo->save($p, true);
        $this->p = $p;

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
        foreach(['perros', 'perro'] as $text) {
            $f = $this->dictionary->find($text, $this->spanish);
            $this->assertTrue($f == null, 'Term not found at first');
        }

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

    public function test_add_term_existing_parent_creates_link() {
        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText('perro');
        $this->dictionary->add($p);

        $perros = new Term();
        $perros->setLanguage($this->spanish);
        $perros->setText('perros');
        $perros->setParentText('perro');
        $this->dictionary->add($perros);

        $f = $this->dictionary->find('perros', $this->spanish);
        $this->assertEquals($perros->getParent()->getID(), $p->getID(), 'parent set');
    }

    public function test_remove_term_removes_term() {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('perro');
        $t->setStatus(1);
        $this->dictionary->add($t);

        $f = $this->dictionary->find('perro', $this->spanish);
        $this->assertEquals($f->getText(), 'perro', 'Term found');
        DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='perro'", 1, 'in db');

        $this->dictionary->remove($t);
        $f = $this->dictionary->find('perro', $this->spanish);
        $this->assertTrue($f == null, 'Term not found');
        DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='perro'", 0, 'not in db');
    }


    public function test_remove_term_leaves_parent_breaks_wordparent_association() {
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

        $this->dictionary->remove($t);
        $f = $this->dictionary->find('perros', $this->spanish);
        $this->assertTrue($f == null, 'perros was removed');
        $f = $this->dictionary->find('perro', $this->spanish);
        $this->assertTrue($f != null, 'perro (parent term) still exists');
        $this->assertEquals($f->getText(), 'perro', 'vive el perro');

        DbHelpers::assertRecordcountEquals('select * from wordparents', 0, 'no assocs');
    }

    public function test_remove_parent_removes_wordparent_record() {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('perros');
        $t->setParentText('perro');
        $t->addTermTag(TermTag::makeTermTag('noun'));
        $this->dictionary->add($t);

        $p = $this->dictionary->find('perro', $this->spanish);
        $this->dictionary->remove($p);
        $f = $this->dictionary->find('perro', $this->spanish);
        $this->assertTrue($f == null, 'perro (parent) was removed');
        $f = $this->dictionary->find('perros', $this->spanish);
        $this->assertTrue($f != null, 'perros (child term) still exists');
        $this->assertEquals($f->getText(), 'perros', 'viven los perros');

        DbHelpers::assertRecordcountEquals('select * from wordparents', 0, 'no assocs');
    }

    public function test_add_term_links_existing_TextItems()
    {
        $text = new Text();
        $text->setLanguage($this->spanish);
        $text->setTitle('hola');
        $text->setText('tengo un gato');
        $this->text_repo->save($text, true);

        $sql = "select ti2textlc from textitems2 where ti2woid <> 0";
        DbHelpers::assertTableContains($sql, [], 'no terms');

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('gato');
        $this->dictionary->add($t);

        DbHelpers::assertTableContains($sql, [ 'gato' ], '1 term');
    }

    public function test_remove_term_unlinks_existing_TextItems()
    {
        $text = new Text();
        $text->setLanguage($this->spanish);
        $text->setTitle('hola');
        $text->setText('tengo un gato');
        $this->text_repo->save($text, true);

        $sql = "select ti2textlc from textitems2 where ti2woid <> 0";
        DbHelpers::assertTableContains($sql, [], 'no terms');

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('gato');
        $this->dictionary->add($t);

        DbHelpers::assertTableContains($sql, [ 'gato' ], '1 term');

        $this->dictionary->remove($t);
        DbHelpers::assertTableContains($sql, [], 'again no terms');
    }

    /**
     * @group dictflush
     */
    public function test_save_and_flush_bulk_updates_text_items() {
        $sentence = 'tengo un gato en mi cuarto';
        $text = new Text();
        $text->setLanguage($this->spanish);
        $text->setTitle('hola');
        $text->setText($sentence);
        $this->text_repo->save($text, true);

        $sql = "select ti2textlc from textitems2 where ti2woid <> 0";
        DbHelpers::assertTableContains($sql, [], 'no terms');

        foreach (explode(' ', $sentence) as $s) {
            $t = new Term();
            $t->setLanguage($this->spanish);
            $t->setText($s);
            $this->dictionary->add($t, false);
        }
        DbHelpers::assertTableContains($sql, [], 'still no mappings');
        $this->dictionary->flush();

        DbHelpers::assertTableContains($sql, explode(' ', $sentence), 'terms created');
    }

    /**
     * @group dictflush
     */
    public function test_save_and_flush_with_multiword_terms_bulk_updates_text_items() {
        $sentence = 'tengo un gato en mi cuarto';
        $text = new Text();
        $text->setLanguage($this->spanish);
        $text->setTitle('hola');
        $text->setText($sentence);
        $this->text_repo->save($text, true);

        $sql = "select ti2textlc from textitems2 where ti2woid <> 0";
        DbHelpers::assertTableContains($sql, [], 'no terms');

        $terms = [ 'tengo', 'un gato' ];
        foreach ($terms as $s) {
            $t = new Term();
            $t->setLanguage($this->spanish);
            $t->setText($s);
            $this->dictionary->add($t, false);
        }
        DbHelpers::assertTableContains($sql, [], 'still no mappings');
        $this->dictionary->flush();

        DbHelpers::assertTableContains($sql, $terms, 'terms created');
    }

}
