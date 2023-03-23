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

    private function make_multipage_book() {
        $b = new Book();
        $b->setTitle("hi");
        $b->setLanguage($this->english);

        // Note that switching the order of these Text() creations
        // doesn't work ... I think that the subsequent find() is
        // using the cached entity.
        $t = new Text();
        $t->setTitle("page 1");
        $t->setLanguage($this->english);
        $t->setText("some more text.");
        $t->setOrder(1);  // PAGE 1
        $b->addText($t);

        $t = new Text();
        $t->setTitle("page 2");
        $t->setOrder(2);
        $t->setLanguage($this->english);
        $t->setText("some text.");
        $b->addText($t);

        $this->book_repo->save($b, true);
        $b->fullParse();

        DbHelpers::assertRecordcountEquals("select * from books", 1, 'b');
        DbHelpers::assertRecordcountEquals("select * from texts", 2, 't');
        DbHelpers::assertRecordcountEquals("select * from sentences", 2, 's');

        return $b;
    }

    public function test_texts_can_be_retrieved_by_index()
    {
        $b = $this->make_multipage_book();
        $bret = $this->book_repo->find($b->getId());
        $this->assertEquals($bret->getTitle(), "hi", "sanity check");
        $this->assertEquals($bret->getTexts()[0]->getText(), "some more text.", "1st page");
        $this->assertEquals($bret->getTexts()[1]->getText(), "some text.", "2nd page");
    }

    /**
     * @group bookarch
     */
    public function test_archive_book_archives_texts() {
        $b = $this->make_multipage_book();
        $b->setArchived(true);
        $this->book_repo->save($b, true);
        DbHelpers::assertRecordcountEquals("select * from books where BkArchived = 1", 1, 'b');
        DbHelpers::assertRecordcountEquals("select * from texts where TxArchived = 1", 2, 'texts archived');
        DbHelpers::assertRecordcountEquals("select * from sentences", 2, 'sentences left');
    }

    /**
     * @group bookdel
     */
    public function test_delete_book_deletes_texts() {
        $b = $this->make_multipage_book();
        $this->book_repo->remove($b, true);
        DbHelpers::assertRecordcountEquals("select * from books", 0, 'b');
        DbHelpers::assertRecordcountEquals("select * from texts", 0, 'texts archived');
        DbHelpers::assertRecordcountEquals("select * from sentences", 0, 'sentences deld');
    }

    /**
     * @group datatables
     */
    public function test_smoke_datatables_query_runs() {
        // smoke test only.

        $columns = [
            0 => [
                "data" => "0",
                "name" => "BkID",
                "searchable" => "false",
                "orderable" => "false"
            ],
            1 => [
                "data" => "1",
                "name" => "BkTitle",
                "searchable" => "true",
                "orderable" => "true"
            ],
        ];
        $params = [
            "draw" => "1",
            "columns" => $columns,
            "order" => [
                0 => [
                    "column" => "1",
                    "dir" => "asc"
                ]
            ],
            "start" => "10",
            "length" => "50",
            "search" => [
                "value" => "",
                "regex" => "false"
            ]
        ];

        $this->book_repo->getDataTablesList($params);
        $this->assertEquals(1, 1, 'smoke');
    }
}
