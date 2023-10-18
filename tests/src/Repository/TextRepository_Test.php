<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Text;

final class TextRepository_Test extends DatabaseTestBase
{

    private Text $text;
    
    public function childSetUp(): void
    {
        // Set up db.
        $this->load_languages();
        // make_text auto-parses the text.
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $this->text = $t;

        DbHelpers::assertRecordcountEquals("sentences", 0, 'no sentences');
        DbHelpers::assertRecordcountEquals("texts", 1, 'setup texts');
    }

    /**
     * @group gensentences_change
     *
     * Sentences should only be generated when a Text is saved with the ReadDate saved.
     * Sentences are only used for reference lookups, 
     */
    public function test_sentence_lifecycle()  // V3-port: TODO
    {
        $t = $this->make_text("Hola.", "Tienes un perro. Un gato.", $this->spanish);

        $sql = "select SeID, SeTxID, SeOrder, SeText from sentences where SeTxID = {$t->getID()}";
        DbHelpers::assertTableContains($sql, [], 'no sentences at first');

        $t->setText("Tengo un gato.  Un perro.");
        $this->text_repo->save($t, true);
        DbHelpers::assertTableContains($sql, [], 'still none');

        $t->setReadDate(new DateTime("now"));
        $this->text_repo->save($t, true);
        $expected = [
            "1; 2; 1; /Tengo/ /un/ /gato/./",
            "2; 2; 2; /Un/ /perro/./"
        ];

        DbHelpers::assertTableContains($sql, $expected, 'sentences generated on readdate set');

        $t->setText("Tienes un perro. Un gato.");
        $this->text_repo->save($t, true);
        $expected = [
            "3; 2; 1; /Tienes/ /un/ /perro/./",
            "4; 2; 2; /Un/ /gato/./"
        ];
        DbHelpers::assertTableContains($sql, $expected, 'sentences changed on save');
    }


    public function test_removing_Text_removes_sentences()  // V3-port: TODO
    {
        $t = $this->text;
        $this->text_repo->remove($t, true);

        DbHelpers::assertRecordcountEquals('sentences', 0, 'after');
        DbHelpers::assertRecordcountEquals("texts", 0, 'after');
    }

}
