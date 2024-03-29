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

    public function test_save()  // V3-port: DONE in tests/unit/models/test_orm_mappings.py
    {
        $b = new Book();
        $b->setTitle("hi");
        $b->setLanguage($this->english);
        $b->setFullText("some text");
        
        $tt = new TextTag();
        $tt->setText("Hola");
        $b->addTag($tt);

        $this->book_repo->save($b, true);

        $sql = "select BkID, BkTitle, BkLgID, BkWordCount from books";
        $expected = [ "{$b->getId()}; hi; {$this->english->getLgId()}; 2" ];
        DbHelpers::assertTableContains($sql, $expected);

        $sql = "select TxBkID, TxOrder, TxText from texts";
        $expected = [ "{$b->getId()}; 1; some text" ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    /**
     * @group booksavefulltext
     */
    public function test_setFullText_replaces_existing_text_entities() {  // V3-port: DONE - not necessary
        $b = new Book();
        $b->setTitle("hi");
        $b->setLanguage($this->english);
        $b->setFullText("some text");
        $this->book_repo->save($b, true);

        $sql = "select TxBkID, TxID, TxOrder, TxText from texts";
        $expected = [ "{$b->getId()}; 1; 1; some text" ];
        DbHelpers::assertTableContains($sql, $expected);

        $b->setFullText("other text");
        $this->book_repo->save($b, true);

        $expected = [ "{$b->getId()}; 2; 1; other text" ];
        DbHelpers::assertTableContains($sql, $expected);
    }

    private function make_multipage_book() {
        $b = Book::makeBook("hi", $this->english, "some more text. some text.", 3);
        $this->book_repo->save($b, true);
        DbHelpers::assertRecordcountEquals("select * from books", 1, 'b');
        DbHelpers::assertRecordcountEquals("select * from texts", 2, 't');
        return $b;
    }

    public function test_texts_can_be_retrieved_by_index()  // V3-port: DONE
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
    public function test_archive_book() {  // V3-port: DONE - not necessary
        $b = $this->make_multipage_book();
        $b->setArchived(true);
        $this->book_repo->save($b, true);
        DbHelpers::assertRecordcountEquals("select * from books where BkArchived = 1", 1, 'b');
    }

    /**
     * @group bookdel
     */
    public function test_delete_book_deletes_texts() {  // V3-port: DONE in test_orm_mappings
        $b = $this->make_multipage_book();
        $this->book_repo->remove($b, true);
        DbHelpers::assertRecordcountEquals("select * from books", 0, 'b');
        DbHelpers::assertRecordcountEquals("select * from texts", 0, 'texts archived');
        DbHelpers::assertRecordcountEquals("select * from sentences", 0, 'sentences deld');
    }

    /**
     * @group datatables
     */
    public function test_smoke_datatables_query_runs() {  // V3-port: DONE test/unit/models/test_book
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
