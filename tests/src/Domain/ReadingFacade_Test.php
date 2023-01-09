<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\ReadingFacade;
use App\Entity\Text;
use App\Domain\Dictionary;
use App\DTO\TermDTO;

final class ReadingFacade_Test extends DatabaseTestBase
{

    private ReadingFacade $facade;
    private Dictionary $dictionary;

    public function childSetUp(): void
    {
        $this->load_languages();

        $dict = new Dictionary($this->term_repo);
        $this->dictionary = $dict;
        $this->facade = new ReadingFacade(
            $this->reading_repo,
            $this->text_repo,
            $this->settings_repo,
            $dict,
            $this->termtag_repo
        );
    }


    public function test_get_sentences_no_sentences() {
        $t = new Text();
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(0, count($sentences), "nothing for new text");
    }

    public function test_get_sentences_with_text()
    {
        $t = $this->create_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(2, count($sentences));
    }

    public function test_get_sentences_reparses_text_if_no_sentences()
    {
        $t = $this->create_text("Hola", "Hola. Adios amigo.", $this->spanish);
        DbHelpers::exec_sql("delete from textitems2");
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(2, count($sentences), "reparsed");
    }


    /**
     * @group associations
     */
    public function test_saving_term_associates_textitems()
    {
        $content = "Hola tengo un gato.";
        $text = $this->create_text("Hola", $content, $this->spanish);

        $sql = "select ti2woid, ti2textlc, wotextlc
          from textitems2
          left join words on wotextlc = ti2textlc
          where ti2textlc = 'tengo'";
        // DbHelpers::dumpTable($textitemssql);
        $expected = [ '0; tengo; ' ];
        DbHelpers::assertTableContains($sql, $expected, "No matches");

        $term = $this->facade->loadDTO(0, $text->getID(), 0, 'tengo');
        $this->facade->saveDTO($term, $text->getID());

        $expected = [ '1; tengo; tengo' ];
        // DbHelpers::dumpTable($wordssql);
        DbHelpers::assertTableContains($sql, $expected, "words created");
    }

    /**
     * @group associations
     */
    public function test_removing_term_disassociates_textitems()
    {
        $content = "Hola tengo un gato.";
        $text = $this->create_text("Hola", $content, $this->spanish);
        $tid = $text->getID();
        $sql = "select ti2woid, ti2textlc, wotextlc
          from textitems2
          left join words on wotextlc = ti2textlc
          where ti2textlc = 'tengo'";
        // DbHelpers::dumpTable($textitemssql);
        $expected = [ '0; tengo; ' ];
        DbHelpers::assertTableContains($sql, $expected, "No matches");

        $term = $this->facade->loadDTO(0, $tid, 0, 'tengo');
        $this->facade->saveDTO($term, $tid);

        $expected = [ '1; tengo; tengo' ];
        // DbHelpers::dumpTable($wordssql);
        DbHelpers::assertTableContains($sql, $expected, "words created");

        $this->facade->removeDTO($term);
        $expected = [ '0; tengo; ' ];
        DbHelpers::assertTableContains($sql, $expected, "mapped back to nothing");
    }


    public function test_mark_unknown_as_known_creates_words_and_updates_ti2s()
    {
        DbHelpers::add_word($this->spanish->getLgID(), "lista", "lista", 3, 1);

        $content = "Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.";
        $t = $this->create_text("Hola", $content, $this->spanish);

        $textitemssql = "select ti2woid, ti2order, ti2text from textitems2
          where ti2wordcount > 0 order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($textitemssql);
        $expected = [
            "0; 1; Hola",
            "0; 3; tengo",
            "0; 5; un",
            "0; 7; gato",
            "0; 10; No",
            "0; 12; tengo",
            "0; 14; una",
            "1; 16; lista",
            "0; 19; Ella",
            "0; 21; tiene",
            "0; 23; una",
            "0; 25; bebida"
        ];
        DbHelpers::assertTableContains($textitemssql, $expected, "initial ti2s");

        $wordssql = "select wotext, wowordcount, wostatus from words order by woid";
        // DbHelpers::dumpTable($wordssql);
        $expected = [
            "lista; 1; 3",
        ];
        DbHelpers::assertTableContains($wordssql, $expected, "initial words");

        // Check mapping.
        $joinedti2s = "select ti2order, ti2text, wotext, wostatus from textitems2
          inner join words on woid = ti2woid
          order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($joinedti2s);
        $expected = [
            "16; lista; lista; 3",
        ];
        DbHelpers::assertTableContains($joinedti2s, $expected, "initial ti2s mapped to words");

        $this->facade->mark_unknowns_as_known($t);

        // DbHelpers::dumpTable($joinedti2s);
        $expected = [
            "1; Hola; hola; 99",
            "3; tengo; tengo; 99",
            "5; un; un; 99",
            "7; gato; gato; 99",
            "10; No; no; 99",
            "12; tengo; tengo; 99",
            "14; una; una; 99",
            "16; lista; lista; 3",
            "19; Ella; ella; 99",
            "21; tiene; tiene; 99",
            "23; una; una; 99",
            "25; bebida; bebida; 99"
        ];
        DbHelpers::assertTableContains($joinedti2s, $expected, "ti2s mapped to words");

    }


    // Prod bug: a text had a textitem2 that it thought was unknown, but a
    // matching term already existed.  Due to a _prior_ bug, the textitem2
    // hadn't been associated to the existing word record, and the
    // facade tried to create the _same_ word on marking this textitem2s as
    // well-known.
    public function test_mark_unknown_as_known_works_if_ti2_already_exists()
    {
        $content = "Hola tengo un perro.";
        $t = $this->create_text("Hola", $content, $this->spanish);

        $textitemssql = "select ti2woid, ti2order, ti2text from textitems2
          where ti2wordcount > 0 order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($textitemssql);
        $expected = [
            "0; 1; Hola",
            "0; 3; tengo",
            "0; 5; un",
            "0; 7; perro"
        ];
        DbHelpers::assertTableContains($textitemssql, $expected, "initial ti2s");

        // Hack db directly to add a word, no associations.
        DbHelpers::add_word($this->spanish->getLgID(), 'perro', 'perro', 1, 1);

        $wordssql = "select wotext, wowordcount, wostatus from words order by woid";
        $expected = [
            "perro; 1; 1",
        ];
        DbHelpers::assertTableContains($wordssql, $expected, "words created");

        // Check mapping.
        $joinedti2s = "select ti2order, ti2text, wotext, wostatus from textitems2
          inner join words on woid = ti2woid
          order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($joinedti2s);
        $expected = [];
        DbHelpers::assertTableContains($joinedti2s, $expected, "no mappings");
        
        $this->facade->mark_unknowns_as_known($t);

        $expected = [
            "perro; 1; 1",  // Not set to 99, because it was already 1.
            "hola; 1; 99",
            "tengo; 1; 99",
            "un; 1; 99"
        ];
        DbHelpers::assertTableContains($wordssql, $expected, "words created");

        // DbHelpers::dumpTable($joinedti2s);
        $expected = [
            "1; Hola; hola; 99",
            "3; tengo; tengo; 99",
            "5; un; un; 99",
            "7; perro; perro; 1"  // Still 1.
        ];
        DbHelpers::assertTableContains($joinedti2s, $expected, "ti2s mapped to words");
    }

    public function test_update_status_creates_words_and_updates_ti2s()
    {
        $this->load_spanish_words();

        $content = "Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.";
        $t = $this->create_text("Hola", $content, $this->spanish);

        $textitemssql = "select ti2woid, ti2order, ti2text from textitems2
          inner join words on woid = ti2woid
          where ti2wordcount > 0 order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($textitemssql);
        $expected = [
            "1; 5; un gato",
            "2; 16; lista",
            "3; 21; tiene una"
        ];
        DbHelpers::assertTableContains($textitemssql, $expected, "initial ti2s");

        $wordssql = "select wotext, wowordcount, wostatus from words order by woid";
        // DbHelpers::dumpTable($wordssql);
        $expected = [
            "Un gato; 2; 1",
            "lista; 1; 1",
            "tiene una; 2; 1",
            "listo; 1; 1"
        ];
        DbHelpers::assertTableContains($wordssql, $expected, "initial words");

        // Check mapping.
        $joinedti2s = "select ti2order, ti2text, wotext, wostatus from textitems2
          inner join words on woid = ti2woid
          order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($joinedti2s);
        $expected = [
            "5; un gato; Un gato; 1",
            "16; lista; lista; 1",
            "21; tiene una; tiene una; 1"
        ];
        DbHelpers::assertTableContains($joinedti2s, $expected, "initial ti2s mapped to words");

        $this->facade->update_status($t, ["tengo", "lista", "perro"], 5);

        $expected = [
            "Un gato; 2; 1",
            "lista; 1; 5", // updated
            "tiene una; 2; 1",
            "listo; 1; 1",
            "tengo; 1; 5", // new
            "perro; 1; 5"  // new, even if not in text, who cares?
        ];
        // DbHelpers::dumpTable($wordssql);
        DbHelpers::assertTableContains($wordssql, $expected, "words created");

        // DbHelpers::dumpTable($joinedti2s);
        $expected = [
            "3; tengo; tengo; 5",
            "5; un gato; Un gato; 1",
            "12; tengo; tengo; 5",
            "16; lista; lista; 5",
            "21; tiene una; tiene una; 1"
        ];
        DbHelpers::assertTableContains($joinedti2s, $expected, "ti2s mapped to words");

    }

    // Prod bug: setting all to known, and then selecting to create a
    // multi-word term, didn't return that new term.
    /**
     * @group prodbug
     */
    public function test_create_multiword_term_when_all_known() {
        $t = $this->create_text("Hola", "Ella tiene una bebida.", $this->spanish);
        $this->facade->mark_unknowns_as_known($t);

        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(count($sentences), 1, "sanity check");
        $sentence = $sentences[0];
        $terms = array_filter($sentence->getTextItems(), fn($ti) => $ti->TextLC == 'tiene');
        $this->assertEquals(count($terms), 1, "just one match, sanity check");
        $tiene = array_values($terms)[0];
        $this->assertEquals($tiene->TextLC, 'tiene', 'sanity check, got the term ...');
        $this->assertTrue($tiene->WoID > 0, '... and it has a WoID');

        $formterm = $this->facade->loadDTO($tiene->WoID, $t->getID(), $tiene->Order, 'tiene una bebida');
        $this->assertEquals($formterm->Text, 'tiene una bebida', 'text loaded');
        $this->assertTrue($formterm->id == null, 'should be a new term');
    }


    private function get_text_textitem_matching($text, $textlc) {
        $all_tis = $this->facade->getTextItems($text);
        $matching_tis = array_filter($all_tis, fn($ti) => $ti->TextLC == $textlc);
        $arr = array_values($matching_tis);
        $this->assertEquals(count($arr), 1, "Guard against ambiguous return textitem!");
        $ti = $arr[0];
        $this->assertEquals($ti->TextLC, $textlc, "sanity check, got textitem for $textlc");
        return $ti;
    }

    // Prod bug: setting all to known in one Text wasn't updating the TextItems in other texts!
    /**
     * @group prodbugknown
     */
    public function test_marking_as_known_updates_other_texts() {
        $bebida_text = $this->create_text("Bebida", "Ella tiene una bebida.", $this->spanish);
        $gato_text = $this->create_text("Gato", "Ella tiene un gato.", $this->spanish);
        $this->facade->mark_unknowns_as_known($bebida_text);

        $gti = function($textlc) use ($gato_text) {
            return $this->get_text_textitem_matching($gato_text, $textlc);
        };
        $ella = $gti('ella');
        $tiene = $gti('tiene');
        $un = $gti('un');
        $gato = $gti('gato');

        $this->assertTrue($ella->WoID != 0, 'ella mapped');
        $this->assertTrue($tiene->WoID != 0, 'tiene mapped');
        $this->assertTrue($un->WoID == 0, 'un NOT mapped');
        $this->assertTrue($gato->WoID == 0, 'gato NOT mapped');
    }


    // Prod bug: when updating the status of an existing multi-term
    // TextItem (that hides other text items), the UI wasn't getting
    // updated, because the ID of the element to replace wasn't
    // correct.
    /**
     * @group prodbug
     */
    public function test_update_multiword_textitem_status_replaces_correct_item() {
        $text = $this->create_text("Hola", "Ella tiene una bebida.", $this->spanish);
        $tid = $text->getID();

        $sentences = $this->facade->getSentences($text);
        $sentence = $sentences[0];

        $spanid_to_text = [];
        foreach ($sentence->getTextItems() as $ti) {
            $spanid_to_text[$ti->getSpanID()] = "'{$ti->TextLC}'";
        }

        $tis = array_filter($sentence->getTextItems(), fn($ti) => $ti->TextLC == 'tiene');
        $tiene = array_values($tis)[0];
        $this->assertEquals($tiene->TextLC, 'tiene', 'sanity check, got the term');
        $this->assertEquals($tiene->WoID, 0, 'sanity check, new word');

        $mword_text = 'tiene una bebida';
        $mword_term = $this->facade->loadDTO($tiene->WoID, $tid, $tiene->Order, $mword_text);
        $mword_term->Status = 1;

        // The new term "tiene una bebida" should replace "tiene" on the UI.
        [ $updatedTIs, $updates ] = $this->facade->saveDTO($mword_term, $tid);
        $this->assertEquals(count($updatedTIs), 1, 'just one update');
        $theTI = $updatedTIs[0];
        $this->assertTrue($theTI->WoID > 0, 'which has an ID');
        $this->assertEquals($theTI->TextLC, $mword_text, 'with the right text');
        $theTI_replaces = $updates[$theTI->getSpanID()]['replace'];
        $this->assertEquals($theTI_replaces, $tiene->getSpanID(), 'it replaces "tiene"');

        $theTI_hides = $updates[$theTI->getSpanID()]['hide'];
        $theTI_hides_text = array_map(fn($ti) => $spanid_to_text[$ti], $theTI_hides);
        $this->assertEquals(
            implode(', ', $theTI_hides_text),
            "' ', 'una', ' ', 'bebida'",
            "Hides stuff after tiene, which it replaces");

        // Get the textitem for "tiene una bebida":
        $sentences = $this->facade->getSentences($text);
        $sentence = $sentences[0];
        $tis = array_filter($sentence->getTextItems(), fn($ti) => $ti->TextLC == $mword_text);
        $mword_ti = array_values($tis)[0];
        $this->assertEquals($mword_ti->TextLC, $mword_text, 'sanity check, got the term');
        $this->assertTrue($mword_ti->WoID > 0, 'and it has a WoID');

        // Load and update the status for "tiene una bebida":        
        $mword_term = $this->facade->loadDTO($mword_ti->WoID, $tid, $mword_ti->Order, '');
        $this->assertEquals($mword_term->id, $mword_ti->WoID, 'sanity check, id');
        $mword_term->Status = 1;

        // The updated term "tiene una bebida" should replace itself on the UI,
        // and hides other things.
        [ $updatedTIs, $updates ] = $this->facade->saveDTO($mword_term, $tid);
        $this->assertEquals(count($updatedTIs), 1, 'just one update');
        $theTI = $updatedTIs[0];
        $this->assertEquals($theTI->WoID, $mword_term->id, 'which is the new term');
        $this->assertEquals($theTI->TextLC, $mword_text, 'with the right text');
        $theTI_replaces = $updates[$theTI->getSpanID()]['replace'];
        $this->assertEquals($theTI_replaces, $theTI->getSpanID(), 'it replaces itself!');

        $theTI_hides = $updates[$theTI->getSpanID()]['hide'];
        $theTI_hides_text = array_map(fn($ti) => $spanid_to_text[$ti], $theTI_hides);
        $this->assertEquals(
            implode(', ', $theTI_hides_text),
            "'tiene', ' ', 'una', ' ', 'bebida'",
            "Hides the original terms (b/c the mword term has replaced itself already)");
    }


    public function test_update_textitem_with_parent() {
        $text = $this->create_text("Tener", "tiene y tener.", $this->spanish);
        $tid = $text->getID();

        $sentences = $this->facade->getSentences($text);
        $sentence = $sentences[0];

        $spanid_to_text = [];
        foreach ($sentence->getTextItems() as $ti) {
            $spanid_to_text[$ti->getSpanID()] = "'{$ti->TextLC}'";
        }

        $tis = array_filter($sentence->getTextItems(), fn($ti) => $ti->TextLC == 'tiene');
        $tiene = array_values($tis)[0];
        $this->assertEquals($tiene->TextLC, 'tiene', 'sanity check, got the term');
        $this->assertEquals($tiene->WoID, 0, 'sanity check, new word');

        $tis = array_filter($sentence->getTextItems(), fn($ti) => $ti->TextLC == 'tener');
        $tener = array_values($tis)[0];
        $this->assertEquals($tener->TextLC, 'tener', 'sanity check, got tener');
        $this->assertEquals($tener->WoID, 0, 'sanity check, new word');

        $term = $this->facade->loadDTO(0, $tid, $tiene->Order, 'tiene');

        $term->ParentText = 'tener';
        $term->Status = 1;

        // The new term "tiene" also updates "tener".
        [ $updatedTIs, $updates ] = $this->facade->saveDTO($term, $tid);
        $this->assertEquals(count($updatedTIs), 2, 'both updated');

        $updated_tiene = $updatedTIs[0];
        $this->assertEquals($updated_tiene->TextLC, 'tiene', 'first update is for tiene');
        $this->assertTrue($updated_tiene->WoID > 0, 'it has an id');
        $updated_tiene_replaces = $updates[$updated_tiene->getSpanID()]['replace'];
        $this->assertEquals($updated_tiene_replaces, $tiene->getSpanID(), 'it replaces "tiene"');

        $tener_term = $this->facade->loadDTO(0, $tid, $tiene->Order, 'tener');
        $this->assertTrue($tener_term->id != 0, 'sanity check, tener also saved.');

        $updated_tener = $updatedTIs[1];
        $this->assertEquals($updated_tener->WoID, $tener_term->id, 'id = tener');
        $this->assertEquals($updated_tener->TextLC, 'tener', 'it says tener');
        $updated_tener_replaces = $updates[$updated_tener->getSpanID()]['replace'];
        $this->assertEquals($updated_tener_replaces, $tener->getSpanID(), 'it replaces "tener"');
    }


    private function get_sentence_textitem($sentence, $textlc) {
        $tis = array_filter($sentence->getTextItems(), fn($ti) => $ti->TextLC == $textlc);
        $ti = array_values($tis)[0];
        $this->assertEquals($ti->TextLC, $textlc, "sanity check, got textitem for $textlc");
        return $ti;
    }

    // Updating a word with parent "que" was also updating "qué"
    /**
     * @group prodbugparent
     */
    public function test_update_textitem_with_parent_and_accent() {
        $text = $this->create_text("Que", "Tengo que y qué.", $this->spanish);
        $tid = $text->getID();

        $sentences = $this->facade->getSentences($text);
        $sentence = $sentences[0];

        $spanid_to_text = [];
        foreach ($sentence->getTextItems() as $ti) {
            $spanid_to_text[$ti->getSpanID()] = "'{$ti->TextLC}'";
        }

        $tengo = $this->get_sentence_textitem($sentence, 'tengo');
        $que = $this->get_sentence_textitem($sentence, 'que');
        $que_accented = $this->get_sentence_textitem($sentence, 'qué');

        $this->assertEquals($tengo->WoID, 0, 'sanity check, new word');

        $term = $this->facade->loadDTO(0, $tid, $tengo->Order, '');
        $this->assertEquals($term->Text, 'Tengo', 'sanity check, have Tengo');
        $term->ParentText = 'que';

        // The new term "tengo" also updates "que", but not "qué".
        [ $updatedTIs, $updates ] = $this->facade->saveDTO($term, $tid);
        $this->assertEquals(count($updatedTIs), 2, 'only 2 updated');
    }


    public function test_get_prev_next_stays_in_current_language() {

        $s1 = $this->create_text("a 1", "Hola.", $this->spanish);
        $s2 = $this->create_text("a 2", "Hola.", $this->spanish);
        $fr = $this->create_text("f", "Bonjour.", $this->french);
        $s3 = $this->create_text("a 3", "Hola.", $this->spanish);

        DbHelpers::assertRecordcountEquals("texts", 4, "sanity check, only 4");
        
        [ $prev, $next ] = $this->facade->get_prev_next($s1);
        $this->assertTrue($prev == null, 's1 prev');
        $this->assertEquals($next->getID(), $s2->getID(), 's1 next');

        [ $prev, $next ] = $this->facade->get_prev_next($s2);
        $this->assertEquals($prev->getID(), $s1->getID(), 's2 prev');
        $this->assertEquals($next->getID(), $s3->getID(), 's2 next');

        [ $prev, $next ] = $this->facade->get_prev_next($s3);
        $this->assertEquals($prev->getID(), $s2->getID(), 's3 prev');
        $this->assertTrue($next == null, 's3 next');

        [ $prev, $next ] = $this->facade->get_prev_next($fr);
        $this->assertTrue($prev == null, 'fr prev');
        $this->assertTrue($next == null, 'fr next');

        // then the rest
    }
    
}
