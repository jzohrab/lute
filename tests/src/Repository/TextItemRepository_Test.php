<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Repository\TextItemRepository;
use App\Entity\Text;
use App\Entity\Term;

final class TextItemRepository_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function test_smoke_test()
    {
        $t = $this->make_text("Gato.", "Un gato es bueno. No hay un gato.  Veo a un gato.", $this->spanish);
        $bueno = $this->addTerms($this->spanish, 'bueno');

        $sql = "select ti2txid, ti2textlc from textitems2 where ti2woid <> 0";
        $tid = $t->getID();
        DbHelpers::assertTableContains($sql, [ "{$tid}; bueno" ]);
    }


    // TextItemRepository was treating "que" and "qué" as the same
    // word, which is wrong.
    public function test_accented_words_are_different()
    {
        $t = $this->make_text("Gato.", "Gato que qué.", $this->spanish);
        $que = $this->addTerms($this->spanish, 'que')[0];
        $sql = "select ti2txid, ti2textlc from textitems2 where ti2woid <> 0";
        $tid = $t->getID();
        DbHelpers::assertTableContains($sql, [ "{$tid}; que" ]);
    }
}
