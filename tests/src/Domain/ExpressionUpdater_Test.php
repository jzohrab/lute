<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\ExpressionUpdater;
use App\Entity\Text;
use App\Entity\Term;

final class ExpressionUpdater_Test extends DatabaseTestBase
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

        $bueno = new Term();
        $bueno->setLanguage($this->spanish);
        $bueno->setText('bueno');
        $this->term_repo->save($bueno, true);

        ExpressionUpdater::associateTermTextItems($bueno);

        $sql = "select ti2txid, ti2textlc from textitems2 where ti2woid <> 0";
        $tid = $t->getID();
        DbHelpers::assertTableContains($sql, [ "{$tid}; bueno" ]);
    }

}
