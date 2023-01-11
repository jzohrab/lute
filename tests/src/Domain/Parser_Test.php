<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/Parser.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\Parser;
use App\Entity\Text;
use App\Entity\Term;

final class Parser_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    // Rewiring the constructor of Parser.
    /**
     * @group rewire
     */
    public function test_existing_cruft_deleted() {
        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $sql = "update textitems2 set ti2text = 'STUFF' where Ti2TxID = {$t->getID()}";
        DbHelpers::exec_sql($sql);
        $sql = "select distinct Ti2TxID FROM textitems2 where ti2Text = 'STUFF'";
        DbHelpers::assertRecordcountEquals($sql, 1, 'before');

        Parser::parse($t);
        DbHelpers::assertRecordcountEquals($sql, 0, 'after');
    }

    // Parsing was failing when the db trigger wasn't defined, so
    // verify it's set!
    public function test_parse_textitems2_textlc_is_set()
    {
        $t = new Text();
        $t->setTitle("Hola");
        $t->setText("Hola");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $sql = "select ti2text, concat('*', ti2textlc, '*') from textitems2 where ti2txid = {$t->getID()}";
        $expected = [ "Hola; *hola*" ];
        DbHelpers::assertTableContains($sql, $expected, 'lowercase set');
    }


    /**
     * @group current
     */
    public function test_parse_no_words_defined()
    {
        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $sql = "select ti2seid, ti2order, ti2text, ti2textlc from textitems2 where ti2woid = 0 order by ti2order";

        $expected = [
            "1; 1; Hola; hola",
            "1; 2;  ;  ",
            "1; 3; tengo; tengo",
            "1; 4;  ;  ",
            "1; 5; un; un",
            "1; 6;  ;  ",
            "1; 7; gato; gato",
            "1; 8; .; .",
            "2; 9;  ;  ",
            "2; 10; No; no",
            "2; 11;  ;  ",
            "2; 12; tengo; tengo",
            "2; 13;  ;  ",
            "2; 14; una; una",
            "2; 15;  ;  ",
            "2; 16; lista; lista",
            "2; 17; .; .",
            "3; 18; ¶; ¶",
            "4; 19; Ella; ella",
            "4; 20;  ;  ",
            "4; 21; tiene; tiene",
            "4; 22;  ;  ",
            "4; 23; una; una",
            "4; 24;  ;  ",
            "4; 25; bebida; bebida",
            "4; 26; .; ."
        ];
        DbHelpers::assertTableContains($sql, $expected, 'after parse');
    }


    public function test_parse_words_defined()
    {
        $this->load_spanish_words();

        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2woid = 0 order by ti2order";
        $expected = [
            "1; 1; Hola",
            "1; 2;  ",
            "1; 3; tengo",
            "1; 4;  ",
            "1; 5; un",
            "1; 6;  ",
            "1; 7; gato",
            "1; 8; .",
            "2; 9;  ",
            "2; 10; No",
            "2; 11;  ",
            "2; 12; tengo",
            "2; 13;  ",
            "2; 14; una",
            "2; 15;  ",
            "2; 17; .",
            "3; 18; ¶",
            "4; 19; Ella",
            "4; 20;  ",
            "4; 21; tiene",
            "4; 22;  ",
            "4; 23; una",
            "4; 24;  ",
            "4; 25; bebida",
            "4; 26; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);

        $sql = "select ti2woid, ti2seid, ti2order, ti2text from textitems2 where ti2woid > 0 order by ti2order";
        $expected = [
            "1; 1; 5; un gato",
            "2; 2; 16; lista",
            "3; 4; 21; tiene una"
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_text_contains_same_term_many_times()
    {
        $this->addTerms($this->spanish, ["Un gato"]);

        $t = new Text();
        $t->setTitle("Gato.");
        $t->setText("Un gato es bueno. No hay un gato.  Veo a un gato.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $sql = "select ti2seid, ti2order, ti2text from textitems2
          where ti2wordcount > 0 order by ti2order, ti2wordcount desc";
        $expected = [
            "1; 1; Un gato",
            "1; 1; Un",
            "1; 3; gato",
            "1; 5; es",
            "1; 7; bueno",
            "2; 10; No",
            "2; 12; hay",
            "2; 14; un gato",
            "2; 14; un",
            "2; 16; gato",
            "3; 19; Veo",
            "3; 21; a",
            "3; 23; un gato",
            "3; 23; un",
            "3; 25; gato"
        ];
        DbHelpers::assertTableContains($sql, $expected);

    }


    public function test_text_same_sentence_contains_same_term_many_times()
    {
        $this->addTerms($this->spanish, ["Un gato"]);

        $t = new Text();
        $t->setTitle("Gato.");
        $t->setText("Un gato es bueno, no hay un gato, veo a un gato.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $sql = "select ti2seid, ti2order, ti2text from textitems2
          where ti2wordcount > 0 order by ti2order, ti2wordcount desc";
        $expected = [
            "1; 1; Un gato",
            "1; 1; Un",
            "1; 3; gato",
            "1; 5; es",
            "1; 7; bueno",
            "1; 9; no",
            "1; 11; hay",
            "1; 13; un gato",
            "1; 13; un",
            "1; 15; gato",
            "1; 17; veo",
            "1; 19; a",
            "1; 21; un gato",
            "1; 21; un",
            "1; 23; gato"
        ];
        DbHelpers::assertTableContains($sql, $expected);

    }


    // While using the legacy code, I ran into problems with specific sentences,
    // and fixed them.  Porting those old tests here.
    public function test_old_production_bugfixes()
    {

        // Misspelling "toddo" in the test case so it doesn't show up in my list of to-do's. :-)
        $sentences = [
            '¿Qué me dice si nos acercamos al bar de la plaza de Sarriá y nos marcamos dos bocadillos de tortilla con muchísima cebolla?',
            'Un doctor de Cáceres le dijo una vez a mi madre que los Romero de Torres éramos el eslabón perdido entre el hombre y el pez martillo, porque el noventa por ciento de nuestro organismo es cartílago, mayormente concentrado en la nariz y en el pabellón auditivo.',
            'En la mesa contigua, un hombre observaba a Fermín de refilón por encima del periódico, probablemente pensando lo mismo que yo.',
            'Pese a toddo lo que pasó luego y a que nos distanciamos con el tiempo, fuimos buenos amigos:',
            'Tanto daba si había pasado el día trabajando en los campos o llevaba encima los mismos harapos de toda la semana.'
            ];
        $t = new Text();
        $t->setTitle("Problemas.");
        $t->setText(implode(' ', $sentences));
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $this->addTerms($this->spanish, [
            'Un gato',
            'de refilón',
            'Con el tiempo',
            'pabellón auditivo',
            'nos marcamos',
            'Tanto daba'
        ]);

        $sql = "select ti2seid, ti2order, ti2text from textitems2
          where ti2woid <> 0 order by ti2seid";
        $expected = [
            '1; 30; nos marcamos',
            '2; 139; pabellón auditivo',
            '3; 162; de refilón',
            '4; 211; con el tiempo',
            '5; 224; Tanto daba'
        ];
        DbHelpers::assertTableContains($sql, $expected);

    }


    // Expression with apostrophe wasn't working, found with demo.
    /**
     * @group apostrophes
     */
    public function test_apostrophes()
    {

        $t = new Text();
        $t->setTitle("Jammies.");
        $t->setText("This is the cat's pyjamas.");
        $t->setLanguage($this->english);
        $this->text_repo->save($t, true);

        $term = $this->addTerms($this->english, "the cat's pyjamas");

        $sql = "select ti2seid, ti2order, ti2text from textitems2
          where ti2woid <> 0 order by ti2seid";
        $expected = [
            "1; 5; the cat's pyjamas"
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    /* "Tests" I was using to echo to console during debugging.
    public function test_verify_regexes() {
        $t = new Text();
        $t->setTitle("Hacky");
        $t->setText("{Hola} `como...`\nYo.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        Parser::parse($t);

        $this->assertEquals(1, 1, 'ok');
    }

    public function test_verify_regexes_split_each() {
        $t = new Text();
        $t->setTitle("Hacky");
        $t->setText("{Hola}.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $this->spanish->setLgSplitEachChar(true);
        Parser::parse($t);
        $this->assertEquals(1, 1, 'ok');
    }
    */


    public function test_parser_loads_stats_for_text() {
        $h = new Text();
        $h->setTitle("Hola");
        $h->setText("Hola tengo un gato.");
        $h->setLanguage($this->spanish);
        $this->text_repo->save($h, true);

        $sql = "select wordcount from textstatscache where TxID = {$h->getID()}";
        DbHelpers::assertTableContains($sql, [ '4' ], "loaded, spot check only");
    }

}
