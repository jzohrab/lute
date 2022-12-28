<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\TermTag;
use App\Entity\Term;
use App\Entity\Text;
use App\Repository\TextItemRepository;

final class TermRepository_Test extends DatabaseTestBase
{

    private TermTag $tag;
    private Term $p;
    private Term $p2;

    public function childSetUp() {
        $this->load_languages();

        $tag = new TermTag();
        $tag->setText("tag");
        $tag->setComment("tag comment");
        $this->termtag_repo->save($tag, true);
        $this->tag = $tag;

        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText("PARENT");
        $p->setStatus(1);
        $p->setWordCount(1);
        $this->term_repo->save($p, true);
        $this->p = $p;

        $p2 = new Term();
        $p2->setLanguage($this->spanish);
        $p2->setText("OTHER");
        $p2->setStatus(1);
        $p2->setWordCount(1);
        $this->term_repo->save($p2, true);
        $this->p2 = $p2;
    }

    public function test_create_and_save()
    {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("HOLA");
        $t->setStatus(1);
        $t->setWordCount(1);
        $t->setTranslation('hi');
        $t->setRomanization('ho-la');
        $this->term_repo->save($t, true);

        $this->assertEquals($t->getTextLC(), 'hola', "sanity check of case");

        $sql = "select WoText, WoTextLC from words where WoID={$t->getID()}";
        $expected = [ "HOLA; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");
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
        $this->term_repo->save($t, true);

        TextItemRepository::mapForTerm($t);

        $sql = "select Ti2WoID, Ti2LgID, Ti2Text from textitems2 order by Ti2LgID";
        $expected = [
            "{$t->getID()}; {$this->spanish->getLgID()}; hoLA",
            "0; {$this->french->getLgID()}; HOLA"
        ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");
    }

    public function test_word_with_parent_and_tags()
    {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("HOLA");
        $t->setStatus(1);
        $t->setWordCount(1);
        $t->setParent($this->p);
        $t->addTermTag($this->tag);
        $this->term_repo->save($t, true);

        $sql = "select WoID, WoText, WoTextLC from words";
        $expected = [ "1; PARENT; parent", "2; OTHER; other", "3; HOLA; hola" ];
        DbHelpers::assertTableContains($sql, $expected, "sanity check on save");

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

    public function test_change_parent()
    {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("HOLA");
        $t->setStatus(1);
        $t->setWordCount(1);
        $t->setParent($this->p);
        $this->term_repo->save($t, true);

        // Hacky sql check.
        $sql = "select w.WoText, p.WoText as ptext
            FROM words w
            LEFT JOIN wordparents on WpWoID = w.WoID
            LEFT JOIN words p on p.WoID = wordparents.WpParentWoID
            WHERE w.WoID = {$t->getID()}";
        $exp = [ "HOLA; PARENT" ];
        DbHelpers::assertTableContains($sql, $exp, "parents, tags");

        $t->setParent($this->p2);
        // TODO:TermDTO - see Term.php.
        $t->setParentText($this->p2->getText());
        $this->term_repo->save($t, true);
        $exp = [ "HOLA; OTHER" ];
        DbHelpers::assertTableContains($sql, $exp, "parents changed, tags");
    }

    public function test_remove_parent()
    {
        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("HOLA");
        $t->setStatus(1);
        $t->setWordCount(1);
        $t->setParent($this->p);
        $this->term_repo->save($t, true);

        // Hacky sql check.
        $sql = "select w.WoText, p.WoText as ptext
            FROM words w
            LEFT JOIN wordparents on WpWoID = w.WoID
            LEFT JOIN words p on p.WoID = wordparents.WpParentWoID
            WHERE w.WoID = {$t->getID()}";
        $exp = [ "HOLA; PARENT" ];
        DbHelpers::assertTableContains($sql, $exp, "parents, tags");

        $t->setParent(null);
        // TODO:TermDTO - see Term.php.
        $t->setParentText(null);
        $this->term_repo->save($t, true);
        $exp = [ "HOLA; " ];
        DbHelpers::assertTableContains($sql, $exp, "parent removed, tags");
    }

    public function test_find_by_text_is_found()
    {
        $cases = [ 'PARENT', 'parent', 'pAReNt' ];
        foreach ($cases as $c) {
            $p = $this->term_repo->findTermInLanguage($c, $this->spanish);
            $this->assertTrue(! is_null($p), 'parent found for case ' . $c);
            $this->assertEquals($p->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    public function test_find_by_text_not_found_returns_null()
    {
        $p = $this->term_repo->findTermInLanguage('SOMETHING_MISSING', $this->spanish);
        $this->assertTrue($p == null, 'nothing found');
    }

    public function test_findTermInLanguage_only_looks_in_specified_language()
    {
        $fp = new Term();
        $fp->setLanguage($this->french);
        $fp->setText("bonjour");
        $fp->setStatus(1);
        $fp->setWordCount(1);
        $this->term_repo->save($fp, true);

        $p = $this->term_repo->findTermInLanguage('bonjour', $this->spanish);
        $this->assertTrue($p == null, 'french terms not checked');
    }

    public function test_findByTextMatch_matching()
    {
        $fp = new Term();
        $fp->setLanguage($this->french);
        $fp->setText("PARENT");
        $fp->setStatus(1);
        $fp->setWordCount(1);
        $this->term_repo->save($fp, true);

        $spid = $this->spanish->getLgID();
        $cases = [ 'ARE', 'are', 'AR' ];
        foreach ($cases as $c) {
            $p = $this->term_repo->findByTextMatchInLanguage($c, $spid);
            $this->assertEquals(count($p), 1, '1 match for case ' . $c . ' in spanish');
            $this->assertEquals($p[0]->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    public function test_findByTextMatch_no_sql_injection()
    {
        $spid = $this->spanish->getLgID();
        $injection = "a%'; select count(*) from words;";
        $p = $this->term_repo->findByTextMatchInLanguage($injection, $spid);
        $this->assertEquals(count($p), 0);
    }

    public function test_findByTextMatch_returns_empty_if_blank_string()
    {
        $spid = $this->spanish->getLgID();
        $p = $this->term_repo->findByTextMatchInLanguage('', $spid);
        $this->assertEquals(count($p), 0);
    }

    public function test_findByTextMatch_returns_empty_for_wrong_language()
    {
        $ft = new Term();
        $ft->setLanguage($this->french);
        $ft->setText("bonjour");
        $ft->setStatus(1);
        $ft->setWordCount(1);
        $this->term_repo->save($ft, true);

        $et = new Term();
        $et->setLanguage($this->english);
        $et->setText("ours");
        $et->setStatus(1);
        $et->setWordCount(1);
        $this->term_repo->save($et, true);

        $spid = $this->spanish->getLgID();
        $p = $this->term_repo->findByTextMatchInLanguage('our', $spid);
        $this->assertEquals(count($p), 0);
    }

    public function test_save_does_not_update_associated_textitems() {
        $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $this->make_text("Bonj.", "Je veux un tengo.", $this->french);

        DbHelpers::assertRecordcountEquals("textitems2", 16, 'sanity check');
        $sql = "select Ti2WoID, Ti2LgID, Ti2WordCount, Ti2Text from textitems2 where Ti2WoID <> 0 order by Ti2Order";
        DbHelpers::assertTableContains($sql, [], "No associations");

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("tengo");
        $this->term_repo->save($t, true);
        DbHelpers::assertTableContains($sql, [], "still no");

        TextItemRepository::mapForTerm($t);
        $expected = [ "{$t->getID()}; 1; 1; tengo" ];
        DbHelpers::assertTableContains($sql, $expected, "_NOW_ associated spanish text");

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("un gato");
        $this->term_repo->save($t, true);
        TextItemRepository::mapForTerm($t);

        $expected[] = "{$t->getID()}; 1; 2; un gato";
        DbHelpers::assertTableContains($sql, $expected, "associated multi-word term");
    }


    // Production bug.
    public function test_save_multiword_term_multiple_times_is_ok() {
        $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        $t = new Term();
        $t->setLanguage($this->spanish);
        $t->setText("un gato");
        $this->term_repo->save($t, true);

        $sql = "select Ti2WoID, Ti2LgID, Ti2WordCount, Ti2Text from textitems2 where Ti2WoID <> 0 order by Ti2Order";
        $expected[] = "{$t->getID()}; 1; 2; un gato";
        TextItemRepository::mapForTerm($t);
        DbHelpers::assertTableContains($sql, $expected, "associated multi-word term");

        // Update and resave
        $t->setStatus(5);
        $this->term_repo->save($t, true);
        DbHelpers::assertTableContains($sql, $expected, "still associated correctly");
    }

    /* Tests
    // TODO:fix Can't change the text of a saved word.
    */
}
