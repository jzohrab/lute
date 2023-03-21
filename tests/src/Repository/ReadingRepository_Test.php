<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Text;
use App\Entity\Term;
use App\Domain\Dictionary;

final class ReadingRepository_Test extends DatabaseTestBase
{

    private Text $text;

    public function childSetUp(): void
    {
        // Set up db.
        $this->load_languages();

        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $this->text = $t;

        $tengo = new Term($this->spanish, 'tengo');
        $tengo->setCurrentImage('tengo.jpg');
        $gato = new Term($this->spanish, 'gato');
        $gato->setCurrentImage('gato.jpg');
        $gato->setParent($tengo);

        // Use dictionary so Terms are associated with TextItems.
        $dict = new Dictionary($this->term_repo);
        $dict->add($tengo, true);
        $dict->add($gato, true);

        DbHelpers::assertRecordcountEquals("texttokens", 8, 'setup tokens');
        DbHelpers::assertRecordcountEquals("wordimages", 2, 'setup images');
        DbHelpers::assertRecordcountEquals("wordparents", 1, 'setup parent');
        DbHelpers::assertRecordcountEquals("sentences", 1, 'setup sentences');
        DbHelpers::assertRecordcountEquals("texts", 1, 'setup texts');
    }

    public function test_smoke_tests()
    {
        $ss = $this->reading_repo->getSentences($this->text);
        $this->assertEquals(1, count($ss), "1 sentence");
        $zws = mb_chr(0x200B);
        $this->assertEquals(str_replace($zws, "/", $ss[0]->SeText), "/Hola/ /tengo/ /un/ /gato/./");
    }

}
