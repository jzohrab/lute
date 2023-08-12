<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\TermService;
use App\Entity\Term;
use App\Entity\Text;
use App\Entity\TermTag;

final class TermService_Test extends DatabaseTestBase
{

    private Term $p;
    private Term $p2;

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    public function test_find_by_text_is_found()
    {
        $this->addTerms($this->spanish, 'PARENT');
        $cases = [ 'PARENT', 'parent', 'pAReNt' ];
        foreach ($cases as $c) {
            $p = $this->term_service->find($c, $this->spanish);
            $this->assertTrue(! is_null($p), 'parent found for case ' . $c);
            $this->assertEquals($p->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    public function test_find_by_text_not_found_returns_null()
    {
        $p = $this->term_service->find('SOMETHING_MISSING', $this->spanish);
        $this->assertTrue($p == null, 'nothing found');
    }

    public function test_find_only_looks_in_specified_language()
    {
        $this->addTerms($this->french, 'bonjour');
        $p = $this->term_service->find('bonjour', $this->spanish);
        $this->assertTrue($p == null, 'french terms not checked');
    }

    public function test_findMatches_matching()
    {
        $this->addTerms($this->spanish, 'PARENT');
        $this->addTerms($this->french, 'PARENT');

        $cases = [ 'PARE', 'pare', 'PAR' ];
        foreach ($cases as $c) {
            $p = $this->term_service->findMatches($c, $this->spanish);
            $this->assertEquals(count($p), 1, '1 match for case ' . $c . ' in spanish');
            $this->assertEquals($p[0]->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    public function test_findMatches_returns_empty_if_blank_string()
    {
        $p = $this->term_service->findMatches('', $this->spanish);
        $this->assertEquals(count($p), 0);
    }

    public function test_findMatches_returns_empty_if_different_language()
    {
        $this->addTerms($this->french, 'chien');

        $p = $this->term_service->findMatches('chien', $this->spanish);
        $this->assertEquals(count($p), 0, "no chien in spanish");

        $p = $this->term_service->findMatches('chien', $this->french);
        $this->assertEquals(count($p), 1, "mais oui il y a un chien ici");
    }


    public function test_add_term_saves_term() {
        $term = new Term();
        $term->setLanguage($this->spanish);
        $term->setText('perro');
        $this->term_service->add($term);

        $f = $this->term_service->find('perro', $this->spanish);
        $this->assertEquals($f->getText(), 'perro', 'Term found');
        DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='perro'", 1, 'in db');
    }

    public function test_add_term_with_new_parent()
    {
        foreach(['perros', 'perro'] as $text) {
            $f = $this->term_service->find($text, $this->spanish);
            $this->assertTrue($f == null, 'Term not found at first');
        }

        $p = new Term($this->spanish, 'perro');
        $t = new Term($this->spanish, 'perros');
        $t->setParent($p);
        $t->addTermTag(TermTag::makeTermTag('noun'));
        $this->term_service->add($t);

        foreach(['perros', 'perro'] as $text) {
            $f = $this->term_service->find($text, $this->spanish);
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
        $this->term_service->add($p);

        $perros = new Term($this->spanish, 'perros');
        $perros->setParent($p);
        $this->term_service->add($perros);

        $f = $this->term_service->find('perros', $this->spanish);
        $this->assertEquals($perros->getParent()->getID(), $p->getID(), 'parent set');
    }

    public function test_remove_term_removes_term() {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('perro');
        $t->setStatus(1);
        $this->term_service->add($t);

        $f = $this->term_service->find('perro', $this->spanish);
        $this->assertEquals($f->getText(), 'perro', 'Term found');
        DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='perro'", 1, 'in db');

        $this->term_service->remove($t);
        $f = $this->term_service->find('perro', $this->spanish);
        $this->assertTrue($f == null, 'Term not found');
        DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='perro'", 0, 'not in db');
    }


    public function test_remove_term_leaves_parent_breaks_wordparent_association() {
        $p = new Term($this->spanish, 'perro');
        $t = new Term($this->spanish, 'perros');
        $t->setParent($p);
        $t->addTermTag(TermTag::makeTermTag('noun'));
        $this->term_service->add($t);

        foreach(['perros', 'perro'] as $text) {
            $f = $this->term_service->find($text, $this->spanish);
            $this->assertEquals($f->getText(), $text, 'Term found');
            DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='{$text}'", 1, 'in db');
        }

        $this->term_service->remove($t);
        $f = $this->term_service->find('perros', $this->spanish);
        $this->assertTrue($f == null, 'perros was removed');
        $f = $this->term_service->find('perro', $this->spanish);
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
        $this->term_service->add($t);

        $p = $this->term_service->find('perro', $this->spanish);
        $this->term_service->remove($p);
        $f = $this->term_service->find('perro', $this->spanish);
        $this->assertTrue($f == null, 'perro (parent) was removed');
        $f = $this->term_service->find('perros', $this->spanish);
        $this->assertTrue($f != null, 'perros (child term) still exists');
        $this->assertEquals($f->getText(), 'perros', 'viven los perros');

        DbHelpers::assertRecordcountEquals('select * from wordparents', 0, 'no assocs');
    }

    /**
     * @group changeRemove
     */
    public function test_change_parent_removes_old_wordparent_record() {
        $parent = new Term($this->spanish, 'perro');
        $this->term_service->add($parent, true);

        $gato = new Term($this->spanish, 'gato');
        $this->term_service->add($gato, true);

        $t = new Term($this->spanish, 'perros');
        $t->setParent($parent);
        $this->term_service->add($t, true);

        $expected = [ "{$t->getID()}; {$parent->getID()}" ];
        DbHelpers::assertTableContains("select WpWoID, WpParentWoID from wordparents", $expected, "parent set");

        $t->setParent($gato);
        $this->term_service->add($t, true);

        $expected = [ "{$t->getID()}; {$gato->getID()}" ];
        DbHelpers::assertTableContains("select WpWoID, WpParentWoID from wordparents", $expected, "NEW parent set");

        $t->setParent(null);
        $this->term_service->add($t, true);
        DbHelpers::assertRecordcountEquals('select * from wordparents', 0, 'no assocs');

        foreach(['perros', 'perro', 'gato'] as $s) {
            $f = $this->term_service->find($s, $this->spanish);
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
        $this->term_service->add($t);

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato(1)");
    }

    public function test_remove_term_unlinks_existing_TextItems()
    {
        $text = $this->make_text('hola', 'tengo un gato', $this->spanish);

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('gato');
        $this->term_service->add($t);
        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato(1)");

        $this->term_service->remove($t);
        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");
    }

    /**
     * @group findAllInString
     */
    public function test_findAllInString() {
        $p = new Term($this->spanish, 'perro');
        $g = new Term($this->spanish, 'gato');
        $ug = new Term($this->spanish, 'un gato');
        $this->term_repo->save($p, true);
        $this->term_repo->save($g, true);
        $this->term_repo->save($ug, true);

        $terms = $this->term_service->findAllInString('Hola tengo un gato', $this->spanish);
        $this->assertEquals(2, count($terms), "2 terms");
        $this->assertEquals('gato', $terms[0]->getTextLC(), 'gato found');
        $zws = mb_chr(0x200B);
        $this->assertEquals("un{$zws} {$zws}gato", $terms[1]->getTextLC(), 'un gato found');
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
            $this->term_service->add($t, false);
        }

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");
        $this->term_service->flush();
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
            $this->term_service->add($t, false);
        }

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");
        $this->term_service->flush();
        $this->assert_rendered_text_equals($text, "tengo(1)/ /un gato(1)");
    }

    /**
     * @group dictrefs
     */
    public function test_get_references_smoke_test()
    {
        $text = $this->make_text('hola', 'Tengo un gato.  No tengo un perro.', $this->spanish);
        $text->setReadDate(new DateTime("now"));
        $this->text_repo->save($text, true);

        $tengo = $this->addTerms($this->spanish, 'tengo')[0];
        $refs = $this->term_service->findReferences($tengo);

        $keys = array_keys($refs);
        $this->assertEquals([ 'term', 'parent', 'children', 'siblings' ], $keys);

        $this->assertEquals(2, count($refs['term']), 'term');
        $this->assertEquals(0, count($refs['parent']), 'parent');
        $this->assertEquals(0, count($refs['siblings']), 'siblings');

        $trs = $refs['term'];
        $zws = mb_chr(0x200B);
        $this->assertEquals("<b>Tengo</b> un gato.", str_replace($zws, '', $trs[0]->Sentence));
        $this->assertEquals($text->getID(), $trs[0]->TxID);
        $this->assertEquals("hola (1/1)", $trs[0]->Title);
        $this->assertEquals("No <b>tengo</b> un perro.", str_replace($zws, '', $trs[1]->Sentence));
    }

    /**
     * @group dictrefsunread
     */
    public function test_get_references_only_includes_read_texts()
    {
        $text = $this->make_text('hola', 'Tengo un gato.  No tengo un perro.', $this->spanish);
        $tengo = $this->addTerms($this->spanish, 'tengo')[0];

        $refs = $this->term_service->findReferences($tengo);
        $keys = array_keys($refs);
        foreach ($keys as $k) {
            $this->assertEquals(0, count($refs[$k]), $k . ', no matches for unread texts');
        }

        $text->setReadDate(new DateTime("now"));
        $this->text_repo->save($text, true);
        $refs = $this->term_service->findReferences($tengo);
        $this->assertEquals(2, count($refs['term']), 'have refs once text is read');
    }

    /**
     * @group dictrefs
     */
    public function test_get_all_references()
    {
        $text = $this->make_text('hola', 'Tengo un gato.  Ella tiene un perro.  No quiero tener nada.', $this->spanish);
        $archtext = $this->make_text('luego', 'Tengo un coche.', $this->spanish);
        foreach ([$text, $archtext] as $t) {
            $t->setReadDate(new DateTime("now"));
            $this->text_repo->save($t, true);
        }

        [ $tengo, $tiene, $tener ] = $this->addTerms($this->spanish, ['tengo', 'tiene', 'tener']);
        $tengo->setParent($tener);
        $tiene->setParent($tener);
        $this->term_service->add($tener, true);
        $this->term_service->add($tiene, true);

        $archtext->setArchived(true);
        $this->text_repo->save($archtext, true);

        $refs = $this->term_service->findReferences($tengo);
        $this->assertEquals(2, count($refs['term']), 'term');
        $this->assertEquals(1, count($refs['parent']), 'parent');
        $this->assertEquals(1, count($refs['siblings']), 'siblings');

        $tostring = function($refdto) {
            $zws = mb_chr(0x200B);
            $ret = implode(', ', [ $refdto->TxID, $refdto->Title, $refdto->Sentence ?? 'NULL' ]);
            return str_replace($zws, '/', $ret);
        };
        $this->assertEquals("1, hola (1/1), /<b>Tengo</b>/ /un/ /gato/./", $tostring($refs['term'][0]), 'term');
        $this->assertEquals("1, hola (1/1), /No/ /quiero/ /<b>tener</b>/ /nada/./", $tostring($refs['parent'][0]), 'p');
        $this->assertEquals("1, hola (1/1), /Ella/ /<b>tiene</b>/ /un/ /perro/./", $tostring($refs['siblings'][0]), 's');
        $this->assertEquals("2, luego (1/1), /<b>Tengo</b>/ /un/ /coche/./", $tostring($refs['term'][1]), 't 1');

        $refs = $this->term_service->findReferences($tener);
        $this->assertEquals(1, count($refs['term']), 'term');
        $this->assertEquals(0, count($refs['parent']), 'parent');
        $this->assertEquals(3, count($refs['children']), 'children');
        $this->assertEquals(0, count($refs['siblings']), 'siblings');

        $this->assertEquals("1, hola (1/1), /No/ /quiero/ /<b>tener</b>/ /nada/./", $tostring($refs['term'][0]), 'term');
        $this->assertEquals("1, hola (1/1), /<b>Tengo</b>/ /un/ /gato/./", $tostring($refs['children'][0]), 'c tener 1');
        $this->assertEquals("2, luego (1/1), /<b>Tengo</b>/ /un/ /coche/./", $tostring($refs['children'][1]), 'c tener 0');
        $this->assertEquals("1, hola (1/1), /Ella/ /<b>tiene</b>/ /un/ /perro/./", $tostring($refs['children'][2]), 'c tener 2');
    }

    /**
     * @group dictrefs
     */
    public function test_archived_references()
    {
        $text = $this->make_text('hola', 'Tengo un gato.  Ella tiene un perro.  No quiero tener nada.', $this->spanish);
        $archtext = $this->make_text('luego', 'Tengo un coche.', $this->spanish);
        foreach ([$text, $archtext] as $t) {
            $t->setReadDate(new DateTime("now"));
            $this->text_repo->save($t, true);
        }

        [ $tengo, $tiene, $tener ] = $this->addTerms($this->spanish, ['tengo', 'tiene', 'tener']);
        $tengo->setParent($tener);
        $tiene->setParent($tener);
        $this->term_service->add($tener, true);
        $this->term_service->add($tiene, true);

        $text->setArchived(true);
        $this->text_repo->save($text, true);
        $archtext->setArchived(true);
        $this->text_repo->save($archtext, true);

        $refs = $this->term_service->findReferences($tengo);

        $tostring = function($refdto) {
            $zws = mb_chr(0x200B);
            $ret = implode(', ', [ $refdto->TxID, $refdto->Title, $refdto->Sentence ?? 'NULL' ]);
            return str_replace($zws, '/', $ret);
        };

        $refs = $this->term_service->findReferences($tengo);

        $this->assertEquals("1, hola (1/1), /<b>Tengo</b>/ /un/ /gato/./", $tostring($refs['term'][0]), 'term 1');
        $this->assertEquals("2, luego (1/1), /<b>Tengo</b>/ /un/ /coche/./", $tostring($refs['term'][1]), 'term 0');
        $this->assertEquals("1, hola (1/1), /No/ /quiero/ /<b>tener</b>/ /nada/./", $tostring($refs['parent'][0]), 'p');
        $this->assertEquals("1, hola (1/1), /Ella/ /<b>tiene</b>/ /un/ /perro/./", $tostring($refs['siblings'][0]), 's');
    }

}
