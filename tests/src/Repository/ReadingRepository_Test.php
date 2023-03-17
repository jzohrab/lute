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

        DbHelpers::assertRecordcountEquals("textitems2", 8, 'setup ti2');
        DbHelpers::assertRecordcountEquals("wordimages", 2, 'setup images');
        DbHelpers::assertRecordcountEquals("wordparents", 1, 'setup parent');
        DbHelpers::assertRecordcountEquals("sentences", 1, 'setup sentences');
        DbHelpers::assertRecordcountEquals("texts", 1, 'setup texts');
    }

    public function test_smoke_test_get_textitems()
    {
        $ti = $this->reading_repo->getTextItems($this->text);
        $this->assertEquals(8, count($ti), "items");

        $gatotis = array_filter($ti, fn($c) => $c->Text == 'gato');
        $this->assertEquals(count($gatotis), 1, 'have gato');
        $gti = array_values($gatotis)[0];
        $this->assertEquals($gti->ImageSource, 'gato.jpg');
        $this->assertEquals($gti->ParentImageSource, 'tengo.jpg');

        $holatis = array_filter($ti, fn($c) => $c->TextLC == 'hola');
        $this->assertEquals(count($holatis), 1, 'have hola');
        $hti = array_values($holatis)[0];
        $this->assertEquals($hti->ImageSource, null);
        $this->assertEquals($hti->ParentImageSource, null);
    }

}
