<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\ReadingFacade;
use App\Domain\BookBinder;
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
            $this->book_repo,
            $this->settings_repo,
            $dict,
            $this->termtag_repo
        );
    }

    // Util methods ------------

    private function save_term($text, $s) {
        $textid = $text->getID();
        $dto = $this->facade->loadDTO(0, $textid, 0, $s);
        $this->facade->saveDTO($dto, $textid);
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
        $stringize = function($ti) {
            $zws = mb_chr(0x200B);
            $status = "({$ti->WoStatus})";
            if ($status == '(0)')
                $status = '';
            return str_replace($zws, '', "{$ti->Text}{$status}");
        };
        $ss = array_map($stringize, $tis);
        return implode('/', $ss);
    }

    private function assert_rendered_text_equals($text, $expected) {
        $s = $this->get_rendered_string($text);
        $this->assertEquals($s, $expected);
    }


    // TESTS -----------------

    public function test_get_sentences_no_sentences() {
        $t = new Text();
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(0, count($sentences), "nothing for new text");
    }

    public function test_get_sentences_with_text()
    {
        $t = $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $sentences = $this->facade->getSentences($t);
        $this->assertEquals(2, count($sentences));
    }

    public function test_get_sentences_reparses_text_if_no_sentences()
    {
        $t = $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
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
        $text = $this->make_text("Hola", $content, $this->spanish);
        $this->assert_rendered_text_equals($text, "Hola/ /tengo/ /un/ /gato/.");

        $tengo = $this->facade->loadDTO(0, $text->getID(), 0, 'tengo');
        $this->facade->saveDTO($tengo, $text->getID());
        $this->assert_rendered_text_equals($text, "Hola/ /tengo(1)/ /un/ /gato/.");
    }

    /**
     * @group associations
     */
    public function test_removing_term_disassociates_textitems()
    {
        $content = "Hola tengo un gato.";
        $text = $this->make_text("Hola", $content, $this->spanish);
        $this->assert_rendered_text_equals($text, "Hola/ /tengo/ /un/ /gato/.");

        $tengo = $this->facade->loadDTO(0, $text->getId(), 0, 'tengo');
        $this->facade->saveDTO($tengo, $text->getId());
        $this->assert_rendered_text_equals($text, "Hola/ /tengo(1)/ /un/ /gato/.");

        $this->facade->removeDTO($tengo);
        $this->assert_rendered_text_equals($text, "Hola/ /tengo/ /un/ /gato/.");
    }


    public function test_mark_unknown_as_known_creates_words_and_updates_ui()
    {
        $this->addTerms($this->spanish, [ 'lista' ]);

        $content = "Tengo un gato. Una lista.\nElla.";
        $t = $this->make_text("Hola", $content, $this->spanish);

        $this->assert_rendered_text_equals($t, "Tengo/ /un/ /gato/./ /Una/ /lista(1)/./¶/Ella/.");

        $wordssql = "select wotext, wowordcount, wostatus from words order by woid";
        $expected = [
            "lista; 1; 1",
        ];
        DbHelpers::assertTableContains($wordssql, $expected, "initial words");

        $this->facade->mark_unknowns_as_known($t);

        $this->assert_rendered_text_equals($t, "Tengo(99)/ /un(99)/ /gato(99)/./ /Una(99)/ /lista(1)/./¶/Ella(99)/.");
        DbHelpers::assertRecordcountEquals($wordssql, 6, "6 words in sentence all created as terms");
    }


    // Prod bug: a text had a textitem2 that it thought was unknown, but a
    // matching term already existed.  Due to a _prior_ bug, the textitem2
    // hadn't been associated to the existing word record, and the
    // facade tried to create the _same_ word on marking this textitem2s as
    // well-known.
    public function test_mark_unknown_as_known_works_if_ti2_already_exists()
    {
        $content = "Hola tengo un perro.";
        $t = $this->make_text("Hola", $content, $this->spanish);

        $this->addTerms($this->spanish, ['perro']);
        $this->assert_rendered_text_equals($t, "Hola/ /tengo/ /un/ /perro(1)/.");
        
        $this->facade->mark_unknowns_as_known($t);
        $this->assert_rendered_text_equals($t, "Hola(99)/ /tengo(99)/ /un(99)/ /perro(1)/.");
    }

    public function test_update_status_creates_words_and_updates_textitems()
    {
        $this->load_spanish_words();

        $content = "Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.";
        $t = $this->make_text("Hola", $content, $this->spanish);

        $this->assert_rendered_text_equals(
            $t,
            "Hola/ /tengo/ /un gato(1)/./ /No/ /tengo/ /una/ /lista(1)/./¶/Ella/ /tiene una(1)/ /bebida/."
        );

        $this->facade->update_status($t, ["tengo", "lista", "perro"], 5);

        $this->assert_rendered_text_equals(
            $t,
            "Hola/ /tengo(5)/ /un gato(1)/./ /No/ /tengo(5)/ /una/ /lista(5)/./¶/Ella/ /tiene una(1)/ /bebida/."
        );

        $expected = [
            "Un/ /gato",
            "lista", // updated
            "tiene/ /una",
            "listo",
            "tengo", // new
            "perro"  // new, even if not in text, who cares?
        ];
        // DbHelpers::dumpTable($wordssql);
        DbHelpers::assertTableContains("select WoText from words", $expected, "words created");
    }

    // Prod bug: setting all to known, and then selecting to create a
    // multi-word term, didn't return that new term.
    public function test_create_multiword_term_when_all_known() {
        $t = $this->make_text("Hola", "Ella tiene una bebida.", $this->spanish);
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
        $bebida_text = $this->make_text("Bebida", "Ella tiene una bebida.", $this->spanish);
        $gato_text = $this->make_text("Gato", "Ella tiene un gato.", $this->spanish);
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
     * @group reload
     */
    public function test_update_multiword_textitem_replaces_correct_item() {
        $text = $this->make_text("Hola", "Ella tiene una bebida.", $this->spanish);
        $this->assert_rendered_text_equals($text, "Ella/ /tiene/ /una/ /bebida/.");

        $this->save_term($text, 'tiene una bebida');
        $this->assert_rendered_text_equals($text, "Ella/ /tiene una bebida(1)/.");

        $this->save_term($text, 'tiene una bebida');
        $this->assert_rendered_text_equals($text, "Ella/ /tiene una bebida(1)/.");
    }

    /**
     * @group reload
     */
    public function test_update_multiword_textitem_with_numbers_replaces_correct_item() {
        $text = $this->make_text("Hola", "121 111 123 \"Ella tiene una bebida\".", $this->spanish);

        $this->assert_rendered_text_equals($text, "121 111 123 \"/Ella/ /tiene/ /una/ /bebida/\".");

        $this->save_term($text, 'tiene una bebida');
        $this->assert_rendered_text_equals($text, "121 111 123 \"/Ella/ /tiene una bebida(1)/\".");

        $this->save_term($text, 'tiene una bebida');
        $this->assert_rendered_text_equals($text, "121 111 123 \"/Ella/ /tiene una bebida(1)/\".");
    }

    // Interesting parser behavious with numbers, it stores spaces with the numbers, treats it as a delimiter.
    /**
     * @group reload
     */
    public function test_update_multiword_textitem_with_numbers_in_middle() {
        $text = $this->make_text("Hola", "Ella tiene 1234 una bebida.", $this->spanish);
        $this->assert_rendered_text_equals($text, "Ella/ /tiene/ 1234 /una/ /bebida/.");

        $this->save_term($text, 'tiene 1234 una bebida');
        $this->assert_rendered_text_equals($text, "Ella/ /tiene 1234 una bebida(1)/.");
    }


    // Japanese multi-word items were getting placed in the wrong location.
    /**
     * @group reload
     */
    public function test_japanese_multiword_stays_in_correct_place() {
        if (!App\Domain\JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = App\Entity\Language::makeJapanese();
        $this->language_repo->save($japanese, true);
        $text = $this->make_text("Hola", "2後ヲウメニ能問アラ費理セイ北多国び持困寿ながち。", $japanese);

        $this->assert_rendered_text_equals($text, "2/後/ヲ/ウメニ/能/問/アラ/費/理/セイ/北/多国/び/持/困/寿/な/がち/。/¶");

        $this->save_term($text, 'ながち');
        $this->assert_rendered_text_equals($text, "2/後/ヲ/ウメニ/能/問/アラ/費/理/セイ/北/多国/び/持/困/寿/ながち(1)/。/¶");
    }


    // Japanese multi-word items were getting placed in the wrong location.
    /**
     * @group reload
     */
    public function test_japanese_multiword_demo_story() {
        if (!App\Domain\JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = App\Entity\Language::makeJapanese();
        $this->language_repo->save($japanese, true);
        $text = $this->make_text("Hola", "「おれの方が強い。」「いいや、ぼくの方が強い。」", $japanese);
        $this->assert_rendered_text_equals($text, "「/おれ/の/方/が/強い/。/」/「/いい/や/、/ぼく/の/方/が/強い/。/」/¶");

        $this->save_term($text, 'ぼくの方');
        $this->assert_rendered_text_equals($text, "「/おれ/の/方/が/強い/。/」/「/いい/や/、/ぼくの方(1)/が/強い/。/」/¶");

        $this->save_term($text, 'おれの方');
        $this->assert_rendered_text_equals($text, "「/おれの方(1)/が/強い/。/」/「/いい/や/、/ぼくの方(1)/が/強い/。/」/¶");
    }


    /**
     * @group reload
     */
     public function test_japanese_multiword_with_numbers() {
        if (!App\Domain\JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $japanese = App\Entity\Language::makeJapanese();
        $this->language_repo->save($japanese, true);
        $text = $this->make_text("Hola", "1234おれの方が強い。", $japanese);
        $this->assert_rendered_text_equals($text, "1234/おれ/の/方/が/強い/。/¶");
        $this->save_term($text, 'おれの方');
        $this->assert_rendered_text_equals($text, "1234/おれの方(1)/が/強い/。/¶");
    }


    /**
     * @group reload
     */
    public function test_update_textitem_with_parent() {
        $text = $this->make_text("Tener", "tiene y tener.", $this->spanish);
        $this->assert_rendered_text_equals($text, "tiene/ /y/ /tener/.");

        // Update "tiene" to have "tener" as parent.
        $tid = $text->getID();
        $tiene = $this->facade->loadDTO(0, $tid, 0, 'tiene');
        $tiene->ParentText = 'tener';
        $tiene->Status = 1;
        $this->facade->saveDTO($tiene, $tid);

        $this->assert_rendered_text_equals($text, "tiene(1)/ /y/ /tener(1)/.");

        $tener = $this->facade->loadDTO(0, $tid, 0, 'tener');
        $this->assertTrue($tener->id != 0, 'sanity check, tener also saved.');
    }

    /**
     * @group reload
     */
    public function test_prod_bug_update_doe_with_parent() {
        $content = "tiene y tener uno.";
        $text = $this->make_text("issue6", $content, $this->english);
        $this->assert_rendered_text_equals($text, "tiene/ /y/ /tener/ /uno/.");

        // Update "tiene" to have "tener uno" as parent.
        $tid = $text->getID();
        $tiene = $this->facade->loadDTO(0, $tid, 0, 'tiene');
        $tiene->ParentText = 'tener uno';
        $tiene->Status = 1;
        $this->facade->saveDTO($tiene, $tid);

        $this->assert_rendered_text_equals($text, "tiene(1)/ /y/ /tener uno(1)/.");
    }


    /**
     * @group issue10
     */
    public function test_multiwords_should_highlight_in_new_text() {
        $text = $this->make_text("AP1", "Tienes un gato.", $this->spanish);
        $tid = $text->getID();
        $dto = $this->facade->loadDTO(0, $tid, 0, 'un gato');
        $this->facade->saveDTO($dto, $tid);

        $this->assert_rendered_text_equals($text, "Tienes/ /un gato(1)/.");

        $this->facade->mark_unknowns_as_known($text);

        $text = $this->make_text("AP2", "Tengo un gato.", $this->spanish);
        $this->assert_rendered_text_equals($text, "Tengo/ /un gato(1)/.");
    }

    /**
     * @group issue10
     */
    public function test_associated_press_multiwords_should_highlight_in_new_text() {
        $ap1 = $this->make_text("AP1", "Abc wrote to the Associated Press about it.", $this->english);
        $ap2 = $this->make_text("AP2", "Def wrote to the Associated Press about it.", $this->english);

        $ap1id = $ap1->getID();
        $dto = $this->facade->loadDTO(0, $ap1id, 0, 'Associated Press');
        $this->facade->saveDTO($dto, $ap1id);
        $this->facade->mark_unknowns_as_known($ap1);

        $this->assert_rendered_text_equals($ap1, "Abc(99)/ /wrote(99)/ /to(99)/ /the(99)/ /Associated Press(1)/ /about(99)/ /it(99)/.");
        $this->assert_rendered_text_equals($ap2, "Def/ /wrote(99)/ /to(99)/ /the(99)/ /Associated Press(1)/ /about(99)/ /it(99)/.");

        $ap3 = $this->make_text("AP3", "Ghi wrote to the Associated Press about it.", $this->english);
        $this->assert_rendered_text_equals($ap3, "Ghi/ /wrote(99)/ /to(99)/ /the(99)/ /Associated Press(1)/ /about(99)/ /it(99)/.");
    }


    private function get_sentence_textitem($sentence, $textlc) {
        $tis = array_filter($sentence->getTextItems(), fn($ti) => $ti->TextLC == $textlc);
        $ti = array_values($tis)[0];
        $this->assertEquals($ti->TextLC, $textlc, "sanity check, got textitem for $textlc");
        return $ti;
    }

    // Updating a word with parent "que" was also updating "qué"
    /**
     * @group reloadcurr
     */
    public function test_update_textitem_with_parent_and_accent() {
        $text = $this->make_text("Que", "Tengo que y qué.", $this->spanish);
        $this->assert_rendered_text_equals($text, "Tengo/ /que/ /y/ /qué/.");

        // Update "tiene" to have "tener uno" as parent.
        $tid = $text->getID();
        $dto = $this->facade->loadDTO(0, $tid, 0, 'tengo');
        $dto->ParentText = 'que';
        $dto->Status = 1;
        $this->facade->saveDTO($dto, $tid);

        // The new term "tengo" also updates "que", but not "qué".
        $this->assert_rendered_text_equals($text, "Tengo(1)/ /que(1)/ /y/ /qué/.");
    }


    /**
     * @group paging
     */
    public function test_get_prev_next_stays_in_current_book() {
        $text = "Here is some text.  And some more. And some more now.";
        $b = BookBinder::makeBook('test', $this->english, $text, 3);
        $this->book_repo->save($b, true);
        $texts = $b->getTexts();
        $this->assertEquals(count($texts), 3, '3 pages');

        $s1 = $texts[0];
        $s2 = $texts[1];
        $s3 = $texts[2];
        
        [ $prev, $next ] = $this->facade->get_prev_next($s1);
        $this->assertTrue($prev == null, 's1 prev');
        $this->assertEquals($next->getID(), $s2->getID(), 's1 next');

        [ $prev, $next ] = $this->facade->get_prev_next($s2);
        $this->assertEquals($prev->getID(), $s1->getID(), 's2 prev');
        $this->assertEquals($next->getID(), $s3->getID(), 's2 next');

        [ $prev, $next ] = $this->facade->get_prev_next($s3);
        $this->assertEquals($prev->getID(), $s2->getID(), 's3 prev');
        $this->assertTrue($next == null, 's3 next');
    }
    
}
