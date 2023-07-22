<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Text;
use App\Entity\Term;
use App\Domain\TermService;

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
        $term_svc = new TermService($this->term_repo);
        $term_svc->add($tengo, true);
        $term_svc->add($gato, true);

        DbHelpers::assertRecordcountEquals("texttokens", 8, 'setup tokens');
        DbHelpers::assertRecordcountEquals("wordimages", 2, 'setup images');
        DbHelpers::assertRecordcountEquals("wordparents", 1, 'setup parent');
        DbHelpers::assertRecordcountEquals("sentences", 1, 'setup sentences');
        DbHelpers::assertRecordcountEquals("texts", 1, 'setup texts');
    }

    public function test_smoke_tests()
    {
        // This used to test a valid method but now it doesn't
        // ... keeping this as a placeholder just in case I want to
        // add some tests to the existing methods.
        $this->assertEquals(1, 1, "dummy test");
    }

}
