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

        $t = new Text();
        $t->setTitle("Hola.");
        $t->setText("Hola tengo un gato.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $this->text = $t;

        DbHelpers::assertRecordcountEquals("textitems2", 8, 'setup ti2');
        DbHelpers::assertRecordcountEquals("sentences", 1, 'setup sentences');
        DbHelpers::assertRecordcountEquals("texts", 1, 'setup texts');
    }

    public function test_saving_Text_entity_loads_textitems2()
    {
        $t = $this->text;
                        
        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2woid = 0 order by ti2order";
        $expected = [
            "1; 1; Hola",
            "1; 2;  ",
            "1; 3; tengo",
            "1; 4;  ",
            "1; 5; un",
            "1; 6;  ",
            "1; 7; gato",
            "1; 8; ."
        ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_saving_Text_replaces_existing_textitems2()
    {
        $t = $this->text;

        $sql = "select ti2seid, ti2order, ti2text from textitems2 where ti2order = 7";
        $sqlsent = "select SeID, SeTxID, SeText from sentences";

        DbHelpers::assertTableContains($sql, [ "1; 7; gato" ]);
        DbHelpers::assertTableContains($sqlsent, [ "1; 1; /Hola/ /tengo/ /un/ /gato/./" ]);

        $t->setText("Hola tengo un perro.");
        $this->text_repo->save($t, true);

        DbHelpers::assertTableContains($sqlsent, [ "2; 1; /Hola/ /tengo/ /un/ /perro/./" ], "sent ID incremented");
        DbHelpers::assertTableContains($sql, [ "2; 7; perro" ], "sentence ID is incremented");
    }

    public function test_removing_Text_removes_sentences_and_textitems2()
    {
        $t = $this->text;
        $this->text_repo->remove($t, true);

        DbHelpers::assertRecordcountEquals('textitems2', 0, 'after');
        DbHelpers::assertRecordcountEquals('sentences', 0, 'after');
        DbHelpers::assertRecordcountEquals("texts", 0, 'after');
    }


    public function test_archiving_Text_removes_textitems2_but_leaves_sentences()
    {
        $t = $this->text;
        $t->setArchived(true);
        $this->text_repo->save($t, true);

        DbHelpers::assertRecordcountEquals('textitems2', 0, 'after');
        DbHelpers::assertRecordcountEquals('sentences', 1, 'after');
        DbHelpers::assertRecordcountEquals("texts", 1, 'after');
    }

    public function test_unarchiving_Text_restores_textitems2()
    {
        $t = $this->text;
        $t->setArchived(true);
        $this->text_repo->save($t, true);
        DbHelpers::assertRecordcountEquals('textitems2', 0, 'arch');

        $t->setArchived(false);
        $this->text_repo->save($t, true);
        DbHelpers::assertRecordcountEquals('textitems2', 8, 'unarch');
    }

}
