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

    public function test_find_by_text_is_found()  // V3-port: TODO
    {
        $this->addTerms($this->spanish, 'PARENT');
        $cases = [ 'PARENT', 'parent', 'pAReNt' ];
        foreach ($cases as $c) {
            $p = $this->term_service->find($c, $this->spanish);
            $this->assertTrue(! is_null($p), 'parent found for case ' . $c);
            $this->assertEquals($p->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    public function test_find_by_text_not_found_returns_null()  // V3-port: TODO
    {
        $p = $this->term_service->find('SOMETHING_MISSING', $this->spanish);
        $this->assertTrue($p == null, 'nothing found');
    }

    public function test_find_only_looks_in_specified_language()  // V3-port: TODO
    {
        $this->addTerms($this->french, 'bonjour');
        $p = $this->term_service->find('bonjour', $this->spanish);
        $this->assertTrue($p == null, 'french terms not checked');
    }

    public function test_findMatches_matching()  // V3-port: TODO
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

    public function test_findMatches_returns_empty_if_blank_string()  // V3-port: TODO
    {
        $p = $this->term_service->findMatches('', $this->spanish);
        $this->assertEquals(count($p), 0);
    }

    public function test_findMatches_returns_empty_if_different_language()  // V3-port: TODO
    {
        $this->addTerms($this->french, 'chien');

        $p = $this->term_service->findMatches('chien', $this->spanish);
        $this->assertEquals(count($p), 0, "no chien in spanish");

        $p = $this->term_service->findMatches('chien', $this->french);
        $this->assertEquals(count($p), 1, "mais oui il y a un chien ici");
    }


    public function test_add_term_saves_term() {  // V3-port: TODO
        $term = new Term();
        $term->setLanguage($this->spanish);
        $term->setText('perro');
        $this->term_service->add($term);

        $f = $this->term_service->find('perro', $this->spanish);
        $this->assertEquals($f->getText(), 'perro', 'Term found');
        DbHelpers::assertRecordcountEquals("select * from words where WoTextLC='perro'", 1, 'in db');
    }

    public function test_add_term_with_new_parent()  // V3-port: TODO
    {
        foreach(['perros', 'perro'] as $text) {
            $f = $this->term_service->find($text, $this->spanish);
            $this->assertTrue($f == null, 'Term not found at first');
        }

        $p = new Term($this->spanish, 'perro');
        $t = new Term($this->spanish, 'perros');
        $t->addParent($p);
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

    public function test_add_term_existing_parent_creates_link() {  // V3-port: TODO
        $p = new Term($this->spanish, 'perro');
        $this->term_service->add($p);

        $perros = new Term($this->spanish, 'perros');
        $perros->addParent($p);
        $this->term_service->add($perros);

        $f = $this->term_service->find('perros', $this->spanish);
        $this->assertEquals($perros->getParents()[0]->getID(), $p->getID(), 'parent set');
    }

    public function test_remove_term_removes_term() {  // V3-port: TODO
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


    public function test_remove_term_leaves_parent_breaks_wordparent_association() {  // V3-port: TODO
        $p = new Term($this->spanish, 'perro');
        $t = new Term($this->spanish, 'perros');
        $t->addParent($p);
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

    public function test_remove_parent_removes_wordparent_record() {  // V3-port: TODO
        $p = new Term($this->spanish, 'perro');
        $t = new Term($this->spanish, 'perros');
        $t->setText('perros');
        $t->addParent($p);
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
    public function test_change_parent_removes_old_wordparent_record() {  // V3-port: TODO
        $parent = new Term($this->spanish, 'perro');
        $this->term_service->add($parent, true);

        $gato = new Term($this->spanish, 'gato');
        $this->term_service->add($gato, true);

        $t = new Term($this->spanish, 'perros');
        $t->addParent($parent);
        $this->term_service->add($t, true);

        $expected = [ "{$t->getID()}; {$parent->getID()}" ];
        DbHelpers::assertTableContains("select WpWoID, WpParentWoID from wordparents", $expected, "parent set");

        $t->addParent($gato);
        $this->term_service->add($t, true);

        $expected = [ "{$t->getID()}; {$parent->getID()}",
                      "{$t->getID()}; {$gato->getID()}" ];
        DbHelpers::assertTableContains("select WpWoID, WpParentWoID from wordparents", $expected, "NEW parent added");

        $t->removeAllParents();
        $this->term_service->add($t, true);
        DbHelpers::assertRecordcountEquals('select * from wordparents', 0, 'all removed');

        foreach(['perros', 'perro', 'gato'] as $s) {
            $f = $this->term_service->find($s, $this->spanish);
            $this->assertTrue($f != null, $s . ' sanity check, still exists');
        }
    }

    public function test_add_term_links_existing_TextItems()  // V3-port: TODO
    {
        $text = $this->make_text('hola', 'tengo un gato', $this->spanish);

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato");

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText('gato');
        $this->term_service->add($t);

        $this->assert_rendered_text_equals($text, "tengo/ /un/ /gato(1)");
    }

    public function test_remove_term_unlinks_existing_TextItems()  // V3-port: TODO
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
    public function test_findAllInString() {  // V3-port: DONE test/unit/read/test_service.py
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
    public function test_save_and_flush_bulk_updates_text_items() {  // V3-port: TODO
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
    public function test_save_and_flush_with_multiword_terms_bulk_updates_text_items() {  // V3-port: TODO
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


    private function full_refs_to_string($refs) {

        $tostring = function($r) {
            return implode(', ', [ $r->TxID, $r->Title, $r->Sentence ?? 'NULL' ]);
        };

        $refs_to_string = function($refs_array) use ($tostring) {
            $ret = [];
            foreach ($refs_array as $r) {
                $ret[] = $tostring($r);
            }
            sort($ret);
            return $ret;
        };

        $parent_refs_to_string = function($prefs) use ($refs_to_string) {
            $ret = [];
            foreach ($prefs as $p) {
                $ret[] = [
                    'term' => $p['term'],
                    'refs' => $refs_to_string($p['refs'])
                ];
            }
            return $ret;
        };

        return [
            'term' => $refs_to_string($refs['term']),
            'children' => $refs_to_string($refs['children']),
            'parents' => $parent_refs_to_string($refs['parents'])
        ];
    }


    /**
     * @group dictrefs_get_all
     */
    public function test_get_all_references()  // V3-port: TODO
    {
        $text = $this->make_text('hola', 'Tengo un gato.  Ella tiene un perro.  No quiero tener nada.', $this->spanish);
        $archtext = $this->make_text('luego', 'Tengo un coche.', $this->spanish);
        $b = $archtext->getBook();
        $b->setArchived(true);
        $this->book_repo->save($b, true);

        foreach ([$text, $archtext] as $t) {
            $t->setReadDate(new DateTime("now"));
            $this->text_repo->save($t, true);
        }

        [ $tengo, $tiene, $tener ] = $this->addTerms($this->spanish, ['tengo', 'tiene', 'tener']);
        $tengo->addParent($tener);
        $this->assertEquals(count($tengo->getParents()), 1, 'has parent');
        $tiene->addParent($tener);
        $this->term_service->add($tengo, true);
        $this->term_service->add($tener, true);
        $this->term_service->add($tiene, true);

        $refs = $this->term_service->findReferences($tengo);
        $this->assertEquals(
            $this->full_refs_to_string($refs),
            [
                'term' => [
                    "1, hola (1/1), <b>Tengo</b> un gato.",
                    "2, luego (1/1), <b>Tengo</b> un coche."
                ],
                'children' => [],
                'parents' => [
                    [
                        'term' => 'tener',
                        'refs' => [
                            "1, hola (1/1), Ella <b>tiene</b> un perro.",
                            "1, hola (1/1), No quiero <b>tener</b> nada."
                        ]
                    ]
                ]
            ],
            'term tengo'
        );


        $refs = $this->term_service->findReferences($tener);
        $this->assertEquals(
            $this->full_refs_to_string($refs),
            [
                'term' => [
                    "1, hola (1/1), No quiero <b>tener</b> nada."
                ],
                'children' => [
                    "1, hola (1/1), <b>Tengo</b> un gato.",
                    "1, hola (1/1), Ella <b>tiene</b> un perro.",
                    "2, luego (1/1), <b>Tengo</b> un coche."
                ],
                'parents' => []
            ],
            'term tener'
        );
    }


    /**
     * @group dictrefsunread
     */
    public function test_get_references_only_includes_read_texts()  // V3-port: TODO
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

}
