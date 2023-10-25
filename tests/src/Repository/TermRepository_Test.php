<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Term;
use App\Entity\Text;
use App\Repository\TermTagRepository;

final class TermRepository_Test extends DatabaseTestBase
{

    public function childSetUp() {
        $this->load_languages();
    }
    
    public function test_save()  // V3-port: DONE in test/orm
    {
        DbHelpers::assertRecordcountEquals("select * from words", 0, "no terms");
        $t = new Term($this->spanish, 'perro');
        $this->term_repo->save($t, true);
        DbHelpers::assertRecordcountEquals("select * from words", 1, "saved");
    }

    public function test_remove()  // V3-port: DONE in test/orm
    {
        DbHelpers::assertRecordcountEquals("select * from words", 0, "no terms");
        $t = new Term($this->spanish, 'perro');
        $this->term_repo->save($t, true);
        DbHelpers::assertRecordcountEquals("select * from words", 1, "saved");
        $this->term_repo->remove($t, true);
        DbHelpers::assertRecordcountEquals("select * from words", 0, "no terms, removed");
    }

    public function test_flush()  // V3-port: DONE - not required
    {
        DbHelpers::assertRecordcountEquals("select * from words", 0, "no terms");
        $t = new Term($this->spanish, 'perro');
        $this->term_repo->save($t, false);
        DbHelpers::assertRecordcountEquals("select * from words", 0, "not saved yet!");
        $this->term_repo->flush();
        DbHelpers::assertRecordcountEquals("select * from words", 1, "now saved");

    }

    public function test_create_and_save()  // V3-port: DONE - not required
    {
        $t = new Term($this->spanish, 'HOLA');
        $t->setStatus(1);
        $t->setTranslation('hi');
        $t->setRomanization('ho-la');
        $this->term_repo->save($t, true);

        $this->assertEquals($t->getTextLC(), 'hola', "sanity check of case");

        $sql = "select WoText, WoTextLC from words where WoID={$t->getID()}";
        $expected = [ "HOLA; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");
    }

    public function test_word_with_parent_and_tags()  // V3-port: DONE in test orm term
    {
        $t = new Term($this->spanish, "HOLA");
        $p = new Term($this->spanish, "PARENT");
        $t->addParent($p);
        $t->addTermTag($this->termtag_repo->findOrCreateByText('tag'));
        $this->term_repo->save($t, true);

        $sql = "select WoID, WoText, WoTextLC from words";
        $expected = [ "1; HOLA; hola", "2; PARENT; parent" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");

        DbHelpers::assertTableContains("select WpWoID, WpParentWoID from wordparents", [ '1; 2' ], 'wp');

        // Hacky sql check.
        $sql = "select w.WoText, p.WoText as ptext, tags.TgText 
            FROM words w
            INNER JOIN wordparents on WpWoID = w.WoID
            INNER JOIN words p on p.WoID = wordparents.WpParentWoID
            INNER JOIN wordtags on WtWoID = w.WoID
            INNER JOIN tags on TgID = WtTgID";
        $exp = [ "HOLA; PARENT; tag" ];
        DbHelpers::assertTableContains($sql, $exp, "parents, tags");
    }

    /**
     * @group getParentAndChildren
     */
    public function test_word_parent_get_child()  // V3-port: DONE in term orm term
    {
        $t = new Term($this->spanish, "HOLA");
        $g = new Term($this->spanish, "gato");
        $p = new Term($this->spanish, "PARENT");
        $g->addParent($p);
        $t->addParent($p);
        $this->term_repo->save($t, true);
        $this->term_repo->save($g, true);

        $pget = $this->term_repo->find($p->getId());
        $this->assertEquals($pget->getText(), "PARENT", "sanity check");
        $this->assertEquals($pget->getChildren()->count(), 2, "2 kids");

        $tget = $this->term_repo->find($t->getId());
        $this->assertEquals($tget->getParents()[0]->getText(), "PARENT", "have text");

        $gget = $this->term_repo->find($g->getId());
        $this->assertEquals($gget->getParents()[0]->getText(), "PARENT", "have text");
    }

    /**
     * @group changeParent
     */
    public function test_change_parent()  // V3-port: DONE - not relevant
    {
        $t = new Term($this->spanish, "HOLA");
        $p = new Term($this->spanish, "PARENT");
        $t->addParent($p);
        $this->term_repo->save($t, true);

        // Hacky sql check.
        $sql = "select w.WoText, p.WoText as ptext
            FROM words w
            LEFT JOIN wordparents on WpWoID = w.WoID
            LEFT JOIN words p on p.WoID = wordparents.WpParentWoID
            WHERE w.WoID = {$t->getID()}";
        $exp = [ "HOLA; PARENT" ];
        DbHelpers::assertTableContains($sql, $exp, "parent");

        $o = new Term($this->spanish, "OTHER");
        $t->removeAllParents();
        $t->addParent($o);
        $this->term_repo->save($t, true);

        $exp = [ "HOLA; OTHER" ];
        DbHelpers::assertTableContains($sql, $exp, "parent changed");
    }

    public function test_set_parent_to_NULL()  // V3-port: DONE - n/a
    {
        $t = new Term($this->spanish, "HOLA");
        $p = new Term($this->spanish, "PARENT");
        $t->addParent($p);
        $this->term_repo->save($t, true);

        // Hacky sql check.
        $sql = "select w.WoText, p.WoText as ptext
            FROM words w
            LEFT JOIN wordparents on WpWoID = w.WoID
            LEFT JOIN words p on p.WoID = wordparents.WpParentWoID
            WHERE w.WoID = {$t->getID()}";
        $exp = [ "HOLA; PARENT" ];
        DbHelpers::assertTableContains($sql, $exp, "parents");

        $t->removeAllParents();
        $this->term_repo->save($t, true);
        $exp = [ "HOLA; NULL" ];
        DbHelpers::assertTableContains($sql, $exp, "parent removed, tags");
    }

    /**
     * @group termremove
     */
    public function test_remove_parent_leaves_children_in_db()  // V3-port: DONE - in test orm test_term
    {
        $t = new Term($this->spanish, "HOLA");
        $p = new Term($this->spanish, "PARENT");
        $t->addParent($p);
        $this->term_repo->save($t, true);

        $sqllist = "select WoText from words order by WoText";
        $sql = "select w.WoText, p.WoText as ptext
            FROM words w
            LEFT JOIN wordparents on WpWoID = w.WoID
            LEFT JOIN words p on p.WoID = wordparents.WpParentWoID
            WHERE w.WoID = {$t->getID()}";

        DbHelpers::assertTableContains($sqllist, [ "HOLA", "PARENT" ], "both exist");
        DbHelpers::assertTableContains($sql, [ "HOLA; PARENT" ], "parent set");

        $pfound = $this->term_repo->find($p->getID());
        $this->term_repo->remove($pfound, true);

        DbHelpers::assertTableContains($sqllist, [ "HOLA" ], "parent removed");
        DbHelpers::assertTableContains($sql, [ "HOLA; NULL" ], "parent not set");
    }

    /**
     * @group termremove
     */
    public function test_can_remove_term_leaves_parent_and_existing_tags()  // V3-port: DONE - in test orm term_term
    {
        $t = new Term($this->spanish, "HOLA");
        $t->addTermTag($this->termtag_repo->findOrCreateByText('tag'));
        $p = new Term($this->spanish, "PARENT");
        $t->addParent($p);
        $this->term_repo->save($t, true);

        $sqllist = "select WoText from words order by WoText";
        $sqltags = "select TgText from tags";

        DbHelpers::assertTableContains($sqllist, [ "HOLA", "PARENT" ], "both exist");
        DbHelpers::assertTableContains($sqltags, [ "tag" ], "tag exists");

        $tfound = $this->term_repo->find($t->getID());
        $this->term_repo->remove($tfound, true);

        DbHelpers::assertTableContains($sqllist, [ "PARENT" ], "parent left");
        DbHelpers::assertTableContains($sqltags, [ "tag" ], "tag left");
    }

    // Image saves:

    /**
     * @group images
     */
    public function test_save_with_image()  // V3-port: DONE - n/a
    {
        $t = new Term($this->spanish, "HOLA");
        $t->setCurrentImage('hello.png');

        $this->assertEquals($t->getCurrentImage(), 'hello.png');
        $this->term_repo->save($t, true);

        $sql = "select WiWoID, WiSource from wordimages";
        $exp = [ "1; hello.png" ];
        DbHelpers::assertTableContains($sql, $exp, "image saved");
    }

    /**
     * @group images
     */
    public function test_save_replace_image()  // V3-port: DONE test orm term
    {
        $t = new Term($this->spanish, "HOLA");
        $t->setCurrentImage('hello.png');
        $this->assertEquals($t->getCurrentImage(), 'hello.png');
        $this->term_repo->save($t, true);

        $sql = "select WiWoID, WiSource from wordimages";
        $exp = [ "1; hello.png" ];
        DbHelpers::assertTableContains($sql, $exp, "image saved");

        $t->setCurrentImage('there.png');
        $this->assertEquals($t->getCurrentImage(), 'there.png');
        $this->term_repo->save($t, true);

        $exp = [ "1; there.png" ];
        DbHelpers::assertTableContains($sql, $exp, "image replaced");
    }

    /**
     * @group images
     */
    public function test_save_remove_image()  // V3-port: DONE test orm term
    {
        $t = new Term($this->spanish, "HOLA");
        $t->setCurrentImage('hello.png');
        $this->assertEquals($t->getCurrentImage(), 'hello.png');
        $this->term_repo->save($t, true);

        $sql = "select WiWoID, WiSource from wordimages";
        $exp = [ "1; hello.png" ];
        DbHelpers::assertTableContains($sql, $exp, "image saved");

        $t->setCurrentImage(null);
        $this->assertEquals($t->getCurrentImage(), null);
        $this->term_repo->save($t, true);

        $exp = [ ];
        DbHelpers::assertTableContains($sql, $exp, "image removed");
    }


    /**
     * @group termflash
     */
    public function test_term_flash_message_mapping() {  // V3-port: DONE test_Term orm flash_message
        $p = new Term($this->spanish, 'perro');
        $this->assertEquals($p->getFlashMessage(), null, "message not set");

        $p->setFlashMessage('hola');
        $this->term_repo->save($p, true);

        $sql = "select WfWoID, WfMessage from wordflashmessages";
        $expected = [ "{$p->getID()}; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");

        $pfind = $this->term_repo->find($p->getID());
        $this->assertEquals($pfind->getFlashMessage(), "hola", "message loaded");
        $this->assertEquals($pfind->getFlashMessage(), "hola", "still set after get() called");
        $this->term_repo->save($pfind, true);
        DbHelpers::assertTableContains($sql, $expected, "still in db");

        $this->assertEquals($pfind->popFlashMessage(), "hola", "message popped");
        $this->assertEquals($pfind->getFlashMessage(), null, "not set after pop");
        $this->term_repo->save($pfind, true);
        $expected = [];
        DbHelpers::assertTableContains($sql, $expected, "removed");
    }

    /**
     * @group termflash
     */
    public function test_can_change_flash_message() {  // V3-port: DONE
        $p = new Term($this->spanish, 'perro');
        $p->setFlashMessage('hola');
        $this->term_repo->save($p, true);

        $sql = "select WfWoID, WfMessage from wordflashmessages";
        $expected = [ "{$p->getID()}; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");

        $pfind = $this->term_repo->find($p->getID());
        $p->setFlashMessage('luego');

        $this->term_repo->save($p, true);
        $expected = [ "{$p->getID()}; luego" ];
        DbHelpers::assertTableContains($sql, $expected, "removed");
    }

    /**
     * @group termflash
     */
    public function test_can_delete_term_with_flash_message() {  // V3-port: DONE test orm test_term
        $p = new Term($this->spanish, 'perro');
        $p->setFlashMessage('hola');
        $this->term_repo->save($p, true);

        $this->term_repo->remove($p, true);
        foreach (["words", "wordflashmessages"] as $t) {
            DbHelpers::assertTableContains("select * from {$t}", [], "$t removed");
        }
    }

    /**
     * @group termflashremoval_1
     */
    public function test_term_flash_can_be_removed() {  // V3-port: DONE test orm test_term
        $p = new Term($this->spanish, 'perro');
        $p->setFlashMessage('hola');
        $this->term_repo->save($p, true);

        $sql = "select WfWoID, WfMessage from wordflashmessages";
        $expected = [ "{$p->getID()}; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");

        $pfind = $this->term_repo->find($p->getID());
        $this->assertEquals($pfind->popFlashMessage(), "hola", "message popped");
        $this->term_repo->save($pfind, true);
        $expected = [];
        DbHelpers::assertTableContains($sql, $expected, "removed");
    }

    // TODO:image_integration_tests Future integration-style tests.
    //
    // Integration tests should remove all images from the userimages
    // folder.  To prevent problems/data loss, there should be a check
    // for some sort of "control file" that the dev has to create in a
    // particular location, or maybe a setting in .env.test/.local, to
    // acknowledge that this will happen and is ok.  Don't want devs
    // to accidentally kill their own personal images.
    //
    // term set current image - downloads if possible (use /public/img/lute.png for tests?)
    // remove term leaves its image in images folder


    /* FIND TESTS ***************************/


    private function assertFindLikeSpecReturns($s, $expected) {
        $spec = new Term($this->spanish, $s);
        $ret = $this->term_repo->findLikeSpecification($spec);
        $this->assertEquals(count($expected), count($ret), $s . " count");
        $actual = join(', ', array_map(fn($t) => $t->getText(), $ret));
        $this->assertEquals(join(', ', $expected), $actual, $s . ' ' . $actual);
    }

    /**
     * @group findLikeSpec
     */
    public function test_findLikeSpecification_initial_check() {  // V3-port: DONE test_Repository
        $t1 = new Term($this->spanish, "abc");
        $t2 = new Term($this->spanish, "abcd");
        $t3 = new Term($this->spanish, "bcd");
        $this->term_repo->save($t1, true);
        $this->term_repo->save($t2, true);
        $this->term_repo->save($t3, true);

        $this->assertFindLikeSpecReturns('ab', [ 'abc', 'abcd' ]);
        $this->assertFindLikeSpecReturns('abcd', [ 'abcd' ]);
        $this->assertFindLikeSpecReturns('bc', [ 'bcd' ]);
        $this->assertFindLikeSpecReturns('yy', [ ]);
    }

    /**
     * @group findLikeSpec
     */
    public function test_findLikeSpecification_terms_with_children_go_to_top() {  // V3-port: DONE test_Repository
        $ap = new Term($this->spanish, "abcPAR");
        $a = new Term($this->spanish, "abc");
        $xp = new Term($this->spanish, "axyPAR");
        $x = new Term($this->spanish, "axy");
        $a->addParent($ap);
        $x->addParent($xp);
        $this->term_repo->save($ap, true);
        $this->term_repo->save($a, true);
        $this->term_repo->save($xp, true);
        $this->term_repo->save($x, true);

        $this->assertFindLikeSpecReturns('a', [ 'abcPAR', 'axyPAR', 'abc', 'axy' ]);
    }

    /**
     * @group findLikeSpec
     */
    public function test_findLikeSpecification_exact_match_trumps_parent() {  // V3-port: DONE test_Repository
        $ap = new Term($this->spanish, "abcPAR");
        $a = new Term($this->spanish, "abc");
        $xp = new Term($this->spanish, "axyPAR");
        $x = new Term($this->spanish, "axy");
        $a->addParent($ap);
        $x->addParent($xp);
        $this->term_repo->save($ap, true);
        $this->term_repo->save($a, true);
        $this->term_repo->save($xp, true);
        $this->term_repo->save($x, true);

        $this->assertFindLikeSpecReturns('abc', [ 'abc', 'abcPAR' ]);
    }

    /**
     * @group findByID
     */
    public function test_findBy_array_of_ids() {  // V3-port: DONE skipping
        $a = new Term($this->spanish, "a");
        $b = new Term($this->spanish, "b");
        $c = new Term($this->spanish, "c");
        $d = new Term($this->spanish, "d");
        $this->term_repo->save($a, true);
        $this->term_repo->save($b, true);
        $this->term_repo->save($c, true);
        $this->term_repo->save($d, true);

        $ids = [ $a->getId(), $b->getId(), $c->getId() ];
        $terms = $this->term_repo->findBy(['id' => $ids]);
        // dump($terms);
        $this->assertEquals(3, count($terms), "3 terms returned");
    }

}
