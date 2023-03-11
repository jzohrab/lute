<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Book;
use App\Entity\TextTag;
use App\Entity\Text;

final class BookRepository_Test extends DatabaseTestBase
{

    public function childSetUp() {
        $this->load_languages();
    }

    public function test_save()
    {
        $b = new Book();
        $b->setTitle("hi");
        $b->setLanguage($this->english);

        $t = new Text();
        $t->setTitle("page1");
        $t->setLanguage($this->english);
        $t->setText("some text");
        $b->addText($t);
        
        $tt = new TextTag();
        $tt->setText("Hola");
        $b->addTag($tt);

        $this->book_repo->save($b, true);

        $sql = "select BkID, BkTitle, BkLgID from books";
        $expected = [ "{$b->getId()}; hi; {$this->english->getLgId()}" ];
        DbHelpers::assertTableContains($sql, $expected);

        $sql = "select TxBkID, TxTitle, TxText from texts";
        $expected = [ "{$b->getId()}; page1; some text" ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    public function test_save_text_orders_must_be_unique()
    {
        $b = new Book();
        $b->setTitle("hi");
        $b->setLanguage($this->english);

        $t = new Text();
        $t->setTitle("page1");
        $t->setLanguage($this->english);
        $t->setText("some text");
        $b->addText($t);

        $t = new Text();
        $t->setTitle("page2");
        $t->setLanguage($this->english);
        $t->setText("some more text");
        $b->addText($t);

        $this->expectException(Exception::class);
        $this->book_repo->save($b, true);
    }


}
