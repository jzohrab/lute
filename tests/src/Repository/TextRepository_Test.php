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

        DbHelpers::assertRecordcountEquals("sentences", 1, 'setup sentences');
        DbHelpers::assertRecordcountEquals("texts", 1, 'setup texts');
    }

    /**
     * @group reworkparsing
     */
    public function test_saving_Text_entity_does_not_load_texttokens()
    {
        $t = $this->make_text("Hola.", "Tengo un gato. Un perro.", $this->spanish);
        $sql = "select TokSentenceNumber, TokOrder, TokIsWord, TokText from texttokens where TokTxID = {$t->getID()} order by TokOrder";
        $expected = [
            "1; 1; 1; Tengo",
            "1; 2; 0;  ",
            "1; 3; 1; un",
            "1; 4; 0;  ",
            "1; 5; 1; gato",
            "1; 6; 0; . ",
            "2; 7; 1; Un",
            "2; 8; 0;  ",
            "2; 9; 1; perro",
            "2; 10; 0; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_parsing_Text_replaces_existing_texttokens()
    {
        $t = $this->text;

        $sql = "select toksentencenumber, tokorder, toktext from texttokens where tokorder = 7";
        $sqlsent = "select SeID, SeTxID, SeText from sentences";

        DbHelpers::assertTableContains($sql, [ "1; 7; gato" ]);
        DbHelpers::assertTableContains($sqlsent, [ "1; 1; /Hola/ /tengo/ /un/ /gato/./" ]);

        $t->setText("Hola tengo un perro.");
        $this->text_repo->save($t, true);
        // Saving a text automatically re-parses it.

        DbHelpers::assertTableContains($sql, [ "1; 7; perro" ], "toksentencenumber is _not_ incremented");
        DbHelpers::assertTableContains($sqlsent, [ "2; 1; /Hola/ /tengo/ /un/ /perro/./" ], "sent ID incremented, text changed");
    }

    public function test_removing_Text_removes_sentences()
    {
        $t = $this->text;
        $this->text_repo->remove($t, true);

        DbHelpers::assertRecordcountEquals('sentences', 0, 'after');
        DbHelpers::assertRecordcountEquals("texts", 0, 'after');
    }


    public function test_archiving_Text_leaves_sentences()
    {
        $t = $this->text;
        $t->setArchived(true);
        $this->text_repo->save($t, true);

        DbHelpers::assertRecordcountEquals('sentences', 1, 'after');
        DbHelpers::assertRecordcountEquals("texts", 1, 'after');
    }

}
