<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\SpaceDelimitedParser;
use App\Domain\ParsedToken;
use App\Entity\Text;
use App\Entity\Term;

final class SpaceDelimitedParser_IntTest extends DatabaseTestBase
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
        $t = $this->make_text("Hola.", "Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.", $this->spanish);

        $sql = "update texttokens set toktext = 'STUFF'";
        DbHelpers::exec_sql($sql);
        $sql = "select distinct Toktext FROM texttokens where toktext = 'STUFF'";
        DbHelpers::assertRecordcountEquals($sql, 1, 'before');

        $t->parse();
        DbHelpers::assertRecordcountEquals($sql, 0, 'after');
    }


    /**
     * @group current
     */
    public function test_parse_no_words_defined()
    {
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $this->assert_rendered_text_equals($t, "Hola/ /tengo/ /un/ /gato/.");
    }



    /**
     * @group manytimes
     */
    public function test_text_contains_same_term_many_times()
    {
        $this->addTerms($this->spanish, ["Un gato"]);

        $t = $this->make_text("Gato.", "Un gato es bueno. No hay un gato.  Veo a un gato.", $this->spanish);
        $this->assert_rendered_text_equals($t, "Un gato(1)/ /es/ /bueno/. /No/ /hay/ /un gato(1)/.  /Veo/ /a/ /un gato(1)/.");
    }


    public function test_text_same_sentence_contains_same_term_many_times()
    {
        $this->addTerms($this->spanish, ["Un gato"]);

        $t = $this->make_text("Gato.", "Un gato es bueno, no hay un gato, veo a un gato.", $this->spanish);
        $this->assert_rendered_text_equals($t, "Un gato(1)/ /es/ /bueno/, /no/ /hay/ /un gato(1)/, /veo/ /a/ /un gato(1)/.");
    }


    // While using the legacy code, I ran into problems with specific sentences,
    // and fixed them.  Porting those old tests here.
    /**
     * @group oldprodbugfixes
     */
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
        $content = implode(' ', $sentences);
        $t = $this->make_text("Problemas.", $content, $this->spanish);

        $added_terms = [
            "Un gato",
            "de refilón",
            "con el tiempo",
            "pabellón auditivo",
            "nos marcamos",
            "Tanto daba"
        ];
        $this->addTerms($this->spanish, $added_terms);

        $stringize = function($ti) {
            if ($ti->TokenCount == 1)
                return $ti->Text;
            $zws = mb_chr(0x200B);
            return str_replace($zws, '', "[[{$ti->Text}]]");
        };

        $expected = $content;
        foreach ($added_terms as $s) {
            $expected = str_replace($s, "[[{$s}]]", $expected);
        }

        $r = $this->get_rendered_string($t, '', $stringize);
        $this->assertEquals($r, $expected);
    }


    // Expression with apostrophe wasn't working, found with demo.
    /**
     * @group apostrophes
     */
    public function test_apostrophes()
    {
        $t = $this->make_text("Jams.", "This is the cat's pyjamas.", $this->english);

        $term = $this->addTerms($this->english, "the cat's pyjamas");
        $this->assert_rendered_text_equals($t, "This/ /is/ /the cat's pyjamas(1)/.");
    }


    // Last word wasn't getting parsed as a word.
    /**
     * @group lastword
     */
    public function test_last_word_is_a_word()
    {
        $t = $this->make_text("Jams.", "Here is a word", $this->english);

        $sql = "select TokText, TokIsWord from texttokens where TokText = 'word'";
        $expected = [
            "word; 1"
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

}
