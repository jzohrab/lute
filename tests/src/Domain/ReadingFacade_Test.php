<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\ReadingFacade;
use App\Entity\Text;

final class ReadingFacade_Test extends DatabaseTestBase
{

    private ReadingFacade $facade;

    public function childSetUp(): void
    {
        $this->load_languages();

        $this->facade = new ReadingFacade(
            $this->reading_repo,
            $this->text_repo,
            $this->term_repo,
            $this->settings_repo
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
     * @group fixing
     */
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
