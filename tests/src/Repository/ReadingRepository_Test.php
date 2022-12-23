<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Text;

final class ReadingRepository_Test extends DatabaseTestBase
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

    public function test_smoke_test_get_textitems()
    {
        $ti = $this->reading_repo->getTextItems($this->text);
        $this->assertEquals(8, count($ti), "items");
    }

}
