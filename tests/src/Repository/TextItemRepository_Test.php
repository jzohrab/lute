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
        $t = new Text();
        $t->setTitle("Gato.");
        $t->setText("Un gato es bueno. No hay un gato.  Veo a un gato.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $bueno = $this->make_term($this->spanish, 'bueno');

        $sql = "select ti2txid, ti2textlc from textitems2 where ti2woid <> 0";
        $tid = $t->getID();
        DbHelpers::assertTableContains($sql, [ "{$tid}; bueno" ]);
    }


    // TextItemRepository was treating "que" and "qué" as the same
    // word, which is wrong.
    public function test_accented_words_are_different()
    {
        $t = new Text();
        $t->setTitle("Gato.");
        $t->setText("Gato que qué.");
        $t->setLanguage($this->spanish);
        $this->text_repo->save($t, true);

        $que = new Term();
        $que->setLanguage($this->spanish);
        $que->setText('que');
        $this->term_repo->save($que, true);

        $sql = "select ti2txid, ti2textlc from textitems2 where ti2woid <> 0";
        DbHelpers::assertTableContains($sql, [], "nothing associated yet");

        TextItemRepository::mapForTerm($que);

        $tid = $t->getID();
        DbHelpers::assertTableContains($sql, [ "{$tid}; que" ]);
    }
}
