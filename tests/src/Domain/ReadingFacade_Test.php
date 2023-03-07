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
        $expected = [ '0; tengo; NULL' ];
        DbHelpers::assertTableContains($sql, $expected, "No matches");

        $tengo = $this->facade->loadDTO(0, $text->getID(), 0, 'tengo');
        $this->facade->saveDTO($tengo, $text->getID());

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
        $expected = [ '0; tengo; NULL' ];
        DbHelpers::assertTableContains($sql, $expected, "No matches");

        $tengo = $this->facade->loadDTO(0, $tid, 0, 'tengo');
        $this->facade->saveDTO($tengo, $tid);

        $expected = [ '1; tengo; tengo' ];
        // DbHelpers::dumpTable($wordssql);
        DbHelpers::assertTableContains($sql, $expected, "words created");

        $this->facade->removeDTO($tengo);
        $expected = [ '0; tengo; NULL' ];
        DbHelpers::assertTableContains($sql, $expected, "mapped back to nothing");
    }


    public function test_mark_unknown_as_known_creates_words_and_updates_ti2s()
    {
        $this->addTerms($this->spanish, [ 'lista' ]);

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
            "lista; 1; 1",
        ];
        DbHelpers::assertTableContains($wordssql, $expected, "initial words");

        // Check mapping.
        $joinedti2s = "select ti2order, ti2text, wotext, wostatus from textitems2
          inner join words on woid = ti2woid
          order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($joinedti2s);
        $expected = [
            "16; lista; lista; 1",
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
            "16; lista; lista; 1",
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

        $this->addTerms($this->spanish, ['perro']);

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
        $expected = [
            "7; perro; perro; 1"
        ];
        DbHelpers::assertTableContains($joinedti2s, $expected, "just perro mapped");
        
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
            "1; 5; un/ /gato",
            "2; 16; lista",
            "3; 21; tiene/ /una"
        ];
        DbHelpers::assertTableContains($textitemssql, $expected, "initial ti2s");

        $wordssql = "select wotext, wowordcount, wostatus from words order by woid";
        // DbHelpers::dumpTable($wordssql);
        $expected = [
            "Un/ /gato; 2; 1",
            "lista; 1; 1",
            "tiene/ /una; 2; 1",
            "listo; 1; 1"
        ];
        DbHelpers::assertTableContains($wordssql, $expected, "initial words");

        // Check mapping.
        $joinedti2s = "select ti2order, ti2text, wotext, wostatus from textitems2
          inner join words on woid = ti2woid
          order by ti2order, ti2wordcount desc";
        // DbHelpers::dumpTable($joinedti2s);
        $expected = [
            "5; un/ /gato; Un/ /gato; 1",
            "16; lista; lista; 1",
            "21; tiene/ /una; tiene/ /una; 1"
        ];
        DbHelpers::assertTableContains($joinedti2s, $expected, "initial ti2s mapped to words");

        $this->facade->update_status($t, ["tengo", "lista", "perro"], 5);

        $expected = [
            "Un/ /gato; 2; 1",
            "lista; 1; 5", // updated
            "tiene/ /una; 2; 1",
            "listo; 1; 1",
            "tengo; 1; 5", // new
            "perro; 1; 5"  // new, even if not in text, who cares?
        ];
        // DbHelpers::dumpTable($wordssql);
        DbHelpers::assertTableContains($wordssql, $expected, "words created");

        // DbHelpers::dumpTable($joinedti2s);
        $expected = [
            "3; tengo; tengo; 5",
            "5; un/ /gato; Un/ /gato; 1",
            "12; tengo; tengo; 5",
            "16; lista; lista; 5",
            "21; tiene/ /una; tiene/ /una; 1"
        ];
        DbHelpers::assertTableContains($joinedti2s, $expected, "ti2s mapped to words");

    }

    // Prod bug: setting all to known, and then selecting to create a
    // multi-word term, didn't return that new term.
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

        $txt = "tiene una bebida";
        $tiene_una_bebida = $this->facade->loadDTO($tiene->WoID, $t->getID(), $tiene->Order, $txt);
        $zws = mb_chr(0x200B);
        $this->assertEquals(str_replace($zws, '', $tiene_una_bebida->Text), $txt, 'text loaded');
        $this->assertTrue($tiene_una_bebida->id == null, 'should be a new term');
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


    /**
     * Helper method for quicker test writing.
     *
     * Given a saved Text $text, create a new Term with text
     * $new_term_text, which replaces the text item with text
     * $replaces_textitem.  The resulting new Term should replace the
     * text item with text $replaces_textitem, and should hide some
     * other text items with text $should_hide.
     */
    private function run_scenario(Text $text, string $new_term_text, string $replaces_textitem, array $should_hide) {
        $tid = $text->getID();

        $sentences = $this->facade->getSentences($text);
        $spanid_to_text = [];
        foreach ($sentences as $sentence) {
            foreach ($sentence->getTextItems() as $ti) {
                $spanid_to_text[$ti->getSpanID()] = "'{$ti->TextLC}'";
            }
        }


        $removeNulls = function($ti) {
            $zws = mb_chr(0x200B);
            return str_replace($zws, '', $ti->TextLC);
        };

        $tis = array_filter(
            $sentence->getTextItems(),
            fn($ti) => ($removeNulls($ti) == $replaces_textitem)
        );
        $this->assertEquals(1, count($tis), 'single match to ensure no ambiguity');
        $replaced_ti = array_values($tis)[0];
        $this->assertEquals($removeNulls($replaced_ti), $replaces_textitem, 'sanity check, got the term');
        // $this->assertEquals($replaced_ti->WoID, 0, 'sanity check, new word');

        $new_dto = $this->facade->loadDTO($replaced_ti->WoID, $tid, $replaced_ti->Order, $new_term_text);
        $new_dto->Status = 1;
        [ $updatedTIs, $updates ] = $this->facade->saveDTO($new_dto, $tid);

        $this->assertEquals(count($updatedTIs), 1, 'just one update');
        $theTI = $updatedTIs[0];
        $this->assertTrue($theTI->WoID > 0, 'which has an ID');

        $zws = mb_chr(0x200B);
        $textlc_no_nulls = str_replace($zws, '', $theTI->TextLC);
        $this->assertEquals($textlc_no_nulls, $new_term_text, 'with the right text');
        $theTI_replaces = $updates[$theTI->getSpanID()]['replace'];
        $this->assertEquals($theTI_replaces, $replaced_ti->getSpanID(), 'it replaces the original');

        $h = array_map(fn($s) => "'{$s}'", $should_hide);
        $theTI_hides = $updates[$theTI->getSpanID()]['hide'];
        $theTI_hides_text = array_map(fn($ti) => $spanid_to_text[$ti], $theTI_hides);
        $this->assertEquals(
            implode(', ', $theTI_hides_text),
            implode(', ', $h),
            "Hides stuff after term, which it replaces");
    }


    // Prod bug: when updating the status of an existing multi-term
    // TextItem (that hides other text items), the UI wasn't getting
    // updated, because the ID of the element to replace wasn't
    // correct.
    public function test_update_multiword_textitem_replaces_correct_item() {
        $text = $this->create_text("Hola", "Ella tiene una bebida.", $this->spanish);
        $txt = "tiene una bebida";
        $this->run_scenario($text, $txt, 'tiene', [ ' ', 'una', ' ', 'bebida' ]);
        $this->run_scenario($text, $txt, $txt, [ 'tiene', ' ', 'una', ' ', 'bebida' ]);
    }

    /**
     * @group rf_zws
     */
    public function test_update_multiword_textitem_with_numbers_replaces_correct_item() {
        $text = $this->create_text("Hola", "121 111 123 \"Ella tiene una bebida\".", $this->spanish);
        $txt = "tiene una bebida";
        $this->run_scenario($text, $txt, 'tiene', [ ' ', 'una', ' ', 'bebida' ]);
        $this->run_scenario($text, $txt, $txt, [ 'tiene', ' ', 'una', ' ', 'bebida' ]);
    }

    // Interesting parser behavious with numbers, it stores spaces with the numbers, treats it as a delimiter.
    public function test_update_multiword_textitem_with_numbers_in_middle() {
        $text = $this->create_text("Hola", "Ella tiene 1234 una bebida.", $this->spanish);
        $txt = "tiene 1234 una bebida";
        $this->run_scenario($text, $txt, 'tiene', [ ' 1234 ', 'una', ' ', 'bebida' ]);
        $this->run_scenario($text, $txt, $txt, [ 'tiene', ' 1234 ', 'una', ' ', 'bebida' ]);
    }


    // Japanese multi-word items were getting placed in the wrong location.
    /**
     * @group japan_reading_multiword
     */
    public function test_japanese_multiword_stays_in_correct_place() {
        if (!App\Domain\JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = App\Entity\Language::makeJapanese();
        $this->language_repo->save($japanese, true);
        $text = $this->create_text("Hola", "2後ヲウメニ能問アラ費理セイ北多国び持困寿ながち。", $japanese);
        $this->run_scenario($text, "ながち", "な", [ 'がち' ]);
        $this->run_scenario($text, "ながち", "ながち", [ "な", "がち" ]);
    }


    // Japanese multi-word items were getting placed in the wrong location.
    /**
     * @group japan_reading_multiword_2
     */
    public function test_japanese_multiword_demo_story() {
        if (!App\Domain\JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = App\Entity\Language::makeJapanese();
        $this->language_repo->save($japanese, true);
        $text = $this->create_text("Hola", "「おれの方が強い。」「いいや、ぼくの方が強い。」", $japanese);
        $this->run_scenario($text, "ぼくの方", "ぼく", [ "の", "方" ]);
        $this->run_scenario($text, "おれの方", "おれ", [ "の", "方" ]);
    }


     public function test_japanese_multiword_with_numbers() {
        if (!App\Domain\JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = App\Entity\Language::makeJapanese();
        $this->language_repo->save($japanese, true);
        $text = $this->create_text("Hola", "1234おれの方が強い。", $japanese);
        $this->run_scenario($text, "おれの方", "おれ", [ "の", "方" ]);
    }


    /**
     * @group textitemparentupdate
     */
    public function test_update_textitem_with_parent() {
        $text = $this->create_text("Tener", "tiene y tener.", $this->spanish);

        $tiene_ti = $this->get_text_textitem_matching($text, 'tiene');
        $this->assertEquals($tiene_ti->TextLC, 'tiene', 'sanity check, got the term');
        $this->assertEquals($tiene_ti->WoID, 0, 'sanity check, new word');

        $tener_ti = $this->get_text_textitem_matching($text, 'tener');
        $this->assertEquals($tener_ti->TextLC, 'tener', 'sanity check, got tener');
        $this->assertEquals($tener_ti->WoID, 0, 'sanity check, new word');

        // Update "tiene" to have "tener" as parent.
        $tid = $text->getID();
        $tiene = $this->facade->loadDTO(0, $tid, $tiene_ti->Order, 'tiene');
        $tiene->ParentText = 'tener';
        $tiene->Status = 1;
        [ $updatedTIs, $updates ] = $this->facade->saveDTO($tiene, $tid);
        $this->assertEquals(count($updatedTIs), 2, 'both updated');

        $tener = $this->facade->loadDTO(0, $tid, $tiene_ti->Order, 'tener');
        $this->assertTrue($tener->id != 0, 'sanity check, tener also saved.');

        $updated_tiene_ti = $updatedTIs[0];
        $this->assertEquals($updated_tiene_ti->TextLC, 'tiene', 'first update is for tiene');
        $this->assertTrue($updated_tiene_ti->WoID > 0, 'it has an id');
        $updated_tiene_ti_replaces = $updates[$updated_tiene_ti->getSpanID()]['replace'];
        $this->assertEquals($updated_tiene_ti_replaces, $tiene_ti->getSpanID(), 'it replaces "tiene"');

        $updated_tener_ti = $updatedTIs[1];
        $this->assertEquals($updated_tener_ti->WoID, $tener->id, 'id = tener');
        $this->assertEquals($updated_tener_ti->TextLC, 'tener', 'it says tener');
        $updated_tener_replaces = $updates[$updated_tener_ti->getSpanID()]['replace'];
        $this->assertEquals($updated_tener_replaces, $tener_ti->getSpanID(), 'it replaces "tener"');
    }

    /**
     * @group issue6
     */
    public function test_prod_bug_update_doe_with_parent() {
        $content = "tiene y tener uno.";
        $text = $this->create_text("issue6", $content, $this->english);

        $tiene_ti = $this->get_text_textitem_matching($text, 'tiene');
        $this->assertEquals($tiene_ti->TextLC, 'tiene', 'sanity check, got the term');
        $this->assertEquals($tiene_ti->WoID, 0, 'sanity check, new word');

        $tener_ti = $this->get_text_textitem_matching($text, 'tener');
        $this->assertEquals($tener_ti->TextLC, 'tener', 'sanity check, got tener');
        $this->assertEquals($tener_ti->WoID, 0, 'sanity check, new word');

        // Update "tiene" to have "tener uno" as parent.
        $tid = $text->getID();
        $tiene = $this->facade->loadDTO(0, $tid, $tiene_ti->Order, 'tiene');
        $tiene->ParentText = 'tener uno';
        $tiene->Status = 1;
        [ $updatedTIs, $updates ] = $this->facade->saveDTO($tiene, $tid);
        // dump($updatedTIs);
        $this->assertEquals(count($updatedTIs), 2, 'two updated');

        // The rest needs to be updated once the failure is cleared.
        $tener = $this->facade->loadDTO(0, $tid, $tiene_ti->Order, 'tener uno');
        $this->assertTrue($tener->id != 0, 'sanity check, tener_uno also saved.');

        $updated_tiene_ti = $updatedTIs[0];
        $this->assertEquals($updated_tiene_ti->TextLC, 'tiene', 'first update is for tiene');
        $this->assertTrue($updated_tiene_ti->WoID > 0, 'it has an id');
        $updated_tiene_ti_replaces = $updates[$updated_tiene_ti->getSpanID()]['replace'];
        $this->assertEquals($updated_tiene_ti_replaces, $tiene_ti->getSpanID(), 'it replaces "tiene"');

        $updated_tener_ti = $updatedTIs[1];
        $this->assertEquals($updated_tener_ti->WoID, $tener->id, 'id = tener');
        $zws = mb_chr(0x200B);
        $this->assertEquals($updated_tener_ti->TextLC, "tener{$zws} {$zws}uno", 'it says tener uno');
        $updated_tener_replaces = $updates[$updated_tener_ti->getSpanID()]['replace'];
        $this->assertEquals($updated_tener_replaces, $tener_ti->getSpanID(), 'it replaces "tener"');

        $hides = $updates[$updated_tener_ti->getSpanID()]['hide'];
        $this->assertEquals(2, count($hides), "hides 2 other things (' ' and 'uno')");
        $hidetexts = $updates[$updated_tener_ti->getSpanID()]['hidetext'];
        $hidetexts = implode('; ', $hidetexts);
        $this->assertEquals('ID-5-1:tener; ID-6-1: ; ID-7-1:uno', $hidetexts, 'hidden items');
    }


    private function get_renderable_textitems($text) {
        $ret = [];
        $ss = $this->facade->getSentences($text);
        foreach ($ss as $s) {
            foreach ($s->renderable() as $ti) {
                $ret[] = $ti;
            }
        }
        return $ret;
    }

    private function get_rendered_string($text) {
        $tis = $this->get_renderable_textitems($text);
        $zws = mb_chr(0x200B);
        $ss = array_map(fn($ti) => str_replace($zws, '', $ti->Text), $tis);
        return implode('/', $ss);
    }

    /**
     * @group issue10
     */
    public function test_multiwords_should_highlight_in_new_text() {
        $text = $this->create_text("AP1", "Tienes un gato.", $this->spanish);
        $tid = $text->getID();
        $dto = $this->facade->loadDTO(0, $tid, 0, 'un gato');
        $this->facade->saveDTO($dto, $tid);

        $s = $this->get_rendered_string($text);
        $this->assertEquals($s, "Tienes/ /un gato/.");

        $this->facade->mark_unknowns_as_known($text);

        $text = $this->create_text("AP2", "Tengo un gato.", $this->spanish);
        $s = $this->get_rendered_string($text);
        $this->assertEquals($s, "Tengo/ /un gato/.");
    }

    /**
     * @group issue10
     */
    public function test_associated_press_multiwords_should_highlight_in_new_text() {
        $ap1 = $this->create_text("AP1", "Abc wrote to the Associated Press about it.", $this->english);
        $ap2 = $this->create_text("AP2", "Def wrote to the Associated Press about it.", $this->english);

        $ap1id = $ap1->getID();
        $dto = $this->facade->loadDTO(0, $ap1id, 0, 'Associated Press');
        $this->facade->saveDTO($dto, $ap1id);
        $this->facade->mark_unknowns_as_known($ap1);

        $s = $this->get_rendered_string($ap1);
        $this->assertEquals($s, "Abc/ /wrote/ /to/ /the/ /Associated Press/ /about/ /it/.");

        $s = $this->get_rendered_string($ap2);
        $this->assertEquals($s, "Def/ /wrote/ /to/ /the/ /Associated Press/ /about/ /it/.");

        $ap3 = $this->create_text("AP3", "Ghi wrote to the Associated Press about it.", $this->english);
        $s = $this->get_rendered_string($ap3);
        $this->assertEquals($s, "Ghi/ /wrote/ /to/ /the/ /Associated Press/ /about/ /it/.");
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

        $tengo_check = $this->facade->loadDTO(0, $tid, $tengo->Order, '');
        $this->assertEquals($tengo_check->Text, 'Tengo', 'sanity check');
        $tengo_check->ParentText = 'que';

        // The new term "tengo" also updates "que", but not "qué".
        [ $updatedTIs, $updates ] = $this->facade->saveDTO($tengo_check, $tid);
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
