<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Term;

final class TermRepository_Test extends DatabaseTestBase
{

    public function childSetUp() {
        $this->load_languages();
    }
    
    public function test_save()
    {
        DbHelpers::assertRecordcountEquals("select * from words", 0, "no terms");
        $t = new Term($this->spanish, 'perro');
        $this->term_repo->save($t, true);
        DbHelpers::assertRecordcountEquals("select * from words", 1, "saved");
    }

    public function test_remove()
    {
        DbHelpers::assertRecordcountEquals("select * from words", 0, "no terms");
        $t = new Term($this->spanish, 'perro');
        $this->term_repo->save($t, true);
        DbHelpers::assertRecordcountEquals("select * from words", 1, "saved");
        $this->term_repo->remove($t, true);
        DbHelpers::assertRecordcountEquals("select * from words", 0, "no terms, removed");
    }

    public function test_flush()
    {
        DbHelpers::assertRecordcountEquals("select * from words", 0, "no terms");
        $t = new Term($this->spanish, 'perro');
        $this->term_repo->save($t, false);
        DbHelpers::assertRecordcountEquals("select * from words", 0, "not saved yet!");
        $this->term_repo->flush();
        DbHelpers::assertRecordcountEquals("select * from words", 1, "now saved");

    }

}
