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
            $this->term_repo
        );
    }

    public function test_find_by_text_is_found()
    {
        $this->addTerms($this->spanish, 'PARENT');
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
        $this->addTerms($this->french, 'bonjour');
        $p = $this->dictionary->find('bonjour', $this->spanish);
        $this->assertTrue($p == null, 'french terms not checked');
    }

    public function test_findMatches_matching()
    {
        $this->addTerms($this->spanish, 'PARENT');
        $this->addTerms($this->french, 'PARENT');

        $cases = [ 'PARE', 'pare', 'PAR' ];
        foreach ($cases as $c) {
            $p = $this->dictionary->findMatches($c, $this->spanish);
            $this->assertEquals(count($p), 1, '1 match for case ' . $c . ' in spanish');
            $this->assertEquals($p[0]->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    public function test_findMatches_returns_empty_if_blank_string()
    {
        $p = $this->dictionary->findMatches('', $this->spanish);
        $this->assertEquals(count($p), 0);
    }

    public function test_findMatches_returns_empty_if_different_language()
    {
        $this->addTerms($this->french, 'chien');

        $p = $this->dictionary->findMatches('chien', $this->spanish);
        $this->assertEquals(count($p), 0, "no chien in spanish");

        $p = $this->dictionary->findMatches('chien', $this->french);
        $this->assertEquals(count($p), 1, "mais oui il y a un chien ici");
    }


    public function test_add_term_saves_term() {
        $term = new Term();
        $term->setLanguage($this->spanish);
        $term->setText('perro');
        $this->dictionary->add($term);

        $f = $this->dictionary->find('perro', $this->spanish);
        $this->assertEquals($f->getText(), 'perro', 'Term found');
        DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='perro'", 1, 'in db');
    }

    public function test_add_term_with_new_parent()
    {
        foreach(['perros', 'perro'] as $text) {
            $f = $this->dictionary->find($text, $this->spanish);
            $this->assertTrue($f == null, 'Term not found at first');
        }

        $p = new Term($this->spanish, 'perro');
        $t = new Term($this->spanish, 'perros');
        $t->setParent($p);
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
        $p = new Term($this->spanish, 'perro');
        $this->dictionary->add($p);

        $perros = new Term($this->spanish, 'perros');
        $perros->setParent($p);
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
        $p = new Term($this->spanish, 'perro');
        $t = new Term($this->spanish, 'perros');
        $t->setParent($p);
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
        $p = new Term($this->spanish, 'perro');
        $t = new Term($this->spanish, 'perros');
        $t->setText('perros');
        $t->setParent($p);
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

    /**
     * @group changeRemove
     */
    public function test_change_parent_removes_old_wordparent_record() {
        $parent = new Term($this->spanish, 'perro');
        $this->dictionary->add($parent, true);

        $gato = new Term($this->spanish, 'gato');
        $this->dictionary->add($gato, true);

        $t = new Term($this->spanish, 'perros');
        $t->setParent($parent);
        $this->dictionary->add($t, true);

        $expected = [ "{$t->getID()}; {$parent->getID()}" ];
        DbHelpers::assertTableContains("select WpWoID, WpParentWoID from wordparents", $expected, "parent set");

        $t->setParent($gato);
        $this->dictionary->add($t, true);

        $expected = [ "{$t->getID()}; {$gato->getID()}" ];
        DbHelpers::assertTableContains("select WpWoID, WpParentWoID from wordparents", $expected, "NEW parent set");

        $t->setParent(null);
        $this->dictionary->add($t, true);
        DbHelpers::assertRecordcountEquals('select * from wordparents', 0, 'no assocs');

        foreach(['perros', 'perro', 'gato'] as $s) {
            $f = $this->dictionary->find($s, $this->spanish);
            $this->assertTrue($f != null, $s . ' sanity check, still exists');
        }
    }

    public function test_add_term_links_existing_TextItems()
    {
        $text = $this->make_text('hola', 'tengo un gato', $this->spanish);

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('gato');
        $this->dictionary->add($t);

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato(1)");
    }

    public function test_remove_term_unlinks_existing_TextItems()
    {
        $text = $this->make_text('hola', 'tengo un gato', $this->spanish);

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('gato');
        $this->dictionary->add($t);
        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato(1)");

        $this->dictionary->remove($t);
        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");
    }

    /**
     * @group dictflush
     */
    public function test_save_and_flush_bulk_updates_text_items() {
        $sentence = 'tengo un gato';
        $text = $this->make_text('hola', $sentence, $this->spanish);

        foreach (explode(' ', $sentence) as $s) {
            $t = new Term();
            $t->setLanguage($this->spanish);
            $t->setText($s);
            $this->dictionary->add($t, false);
        }

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");
        $this->dictionary->flush();
        $this->assert_rendered_text_equals($text, "tengo(1)/ /un(1)/ /gato(1)");
    }

    /**
     * @group dictflush
     */
    public function test_save_and_flush_with_multiword_terms_bulk_updates_text_items() {
        $sentence = 'tengo un gato';
        $text = $this->make_text('hola', $sentence, $this->spanish);

        $terms = [ 'tengo', "un gato" ];
        foreach ($terms as $s) {
            $t = new Term();
            $t->setLanguage($this->spanish);
            $t->setText($s);
            $this->dictionary->add($t, false);
        }

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");
        $this->dictionary->flush();
        $this->assert_rendered_text_equals($text, "tengo(1)/ /un gato(1)");
    }

    /**
     * @group dictrefs
     */
    public function test_get_references_smoke_test()
    {
        $text = $this->make_text('hola', 'Tengo un gato.  No tengo un perro.', $this->spanish);

        $tengo = $this->addTerms($this->spanish, 'tengo')[0];
        $refs = $this->dictionary->findReferences($tengo);

        $keys = array_keys($refs);
        $this->assertEquals([ 'term', 'parent', 'children', 'siblings', 'archived' ], $keys);

        $this->assertEquals(2, count($refs['term']), 'term');
        $this->assertEquals(0, count($refs['parent']), 'parent');
        $this->assertEquals(0, count($refs['siblings']), 'siblings');
        $this->assertEquals(0, count($refs['archived']), 'archived');

        $trs = $refs['term'];
        $zws = mb_chr(0x200B);
        $this->assertEquals("Tengo un gato.", str_replace($zws, '', $trs[0]->Sentence));
        $this->assertEquals($text->getID(), $trs[0]->TxID);
        $this->assertEquals("hola", $trs[0]->Title);
        $this->assertEquals("No tengo un perro.", str_replace($zws, '', $trs[1]->Sentence));
    }

    /**
     * @group dictrefs
     */
    public function test_get_all_references()
    {
        $text = $this->make_text('hola', 'Tengo un gato.  Ella tiene un perro.  No quiero tener nada.', $this->spanish);
        $archtext = $this->make_text('luego', 'Tengo un coche.', $this->spanish);

        [ $tengo, $tiene, $tener ] = $this->addTerms($this->spanish, ['tengo', 'tiene', 'tener']);
        $tengo->setParent($tener);
        $tiene->setParent($tener);
        $this->dictionary->add($tener, true);
        $this->dictionary->add($tiene, true);

        $archtext->setArchived(true);
        $this->text_repo->save($archtext, true);

        $refs = $this->dictionary->findReferences($tengo);
        $this->assertEquals(1, count($refs['term']), 'term');
        $this->assertEquals(1, count($refs['parent']), 'parent');
        $this->assertEquals(1, count($refs['siblings']), 'siblings');
        $this->assertEquals(1, count($refs['archived']), 'archived tengo');

        $tostring = function($refdto) {
            $zws = mb_chr(0x200B);
            $ret = implode(', ', [ $refdto->TxID, $refdto->Title, $refdto->Sentence ?? 'NULL' ]);
            return str_replace($zws, '/', $ret);
        };
        $this->assertEquals("1, hola, /Tengo/ /un/ /gato/./", $tostring($refs['term'][0]), 'term');
        $this->assertEquals("1, hola, /No/ /quiero/ /tener/ /nada/./", $tostring($refs['parent'][0]), 'p');
        $this->assertEquals("1, hola, /Ella/ /tiene/ /un/ /perro/./", $tostring($refs['siblings'][0]), 's');
        $this->assertEquals("2, luego, /Tengo/ /un/ /coche/./", $tostring($refs['archived'][0]), 'a');

        $refs = $this->dictionary->findReferences($tener);
        $this->assertEquals(1, count($refs['term']), 'term');
        $this->assertEquals(0, count($refs['parent']), 'parent');
        $this->assertEquals(2, count($refs['children']), 'children');
        $this->assertEquals(0, count($refs['siblings']), 'siblings');
        $this->assertEquals(1, count($refs['archived']), 'archived tener');

        $this->assertEquals("1, hola, /No/ /quiero/ /tener/ /nada/./", $tostring($refs['term'][0]), 'term');
        $this->assertEquals("1, hola, /Ella/ /tiene/ /un/ /perro/./", $tostring($refs['children'][1]), 'c tener 1');
        $this->assertEquals("1, hola, /Tengo/ /un/ /gato/./", $tostring($refs['children'][0]), 'c tener 0');
        $this->assertEquals("2, luego, /Tengo/ /un/ /coche/./", $tostring($refs['archived'][0]), 'a tener');

    }

    /**
     * @group dictrefs
     */
    public function test_archived_references()
    {
        $text = $this->make_text('hola', 'Tengo un gato.  Ella tiene un perro.  No quiero tener nada.', $this->spanish);
        $archtext = $this->make_text('luego', 'Tengo un coche.', $this->spanish);

        [ $tengo, $tiene, $tener ] = $this->addTerms($this->spanish, ['tengo', 'tiene', 'tener']);
        $tengo->setParent($tener);
        $tiene->setParent($tener);
        $this->dictionary->add($tener, true);
        $this->dictionary->add($tiene, true);

        $text->setArchived(true);
        $this->text_repo->save($text, true);
        $archtext->setArchived(true);
        $this->text_repo->save($archtext, true);

        $refs = $this->dictionary->findReferences($tengo);

        $tostring = function($refdto) {
            $zws = mb_chr(0x200B);
            $ret = implode(', ', [ $refdto->TxID, $refdto->Title, $refdto->Sentence ?? 'NULL' ]);
            return str_replace($zws, '/', $ret);
        };

        $refs = $this->dictionary->findReferences($tengo);
        $archrefs = $refs['archived'];
        $this->assertEquals(4, count($archrefs), 'archived tengo');

        $this->assertEquals("1, hola, /Tengo/ /un/ /gato/./", $tostring($archrefs[0]), 'c tener 0');
        $this->assertEquals("2, luego, /Tengo/ /un/ /coche/./", $tostring($archrefs[1]), 'a tener');
        $this->assertEquals("1, hola, /Ella/ /tiene/ /un/ /perro/./", $tostring($archrefs[2]), 'c tener 1');
        $this->assertEquals("1, hola, /No/ /quiero/ /tener/ /nada/./", $tostring($archrefs[3]), 'term');

    }

}
