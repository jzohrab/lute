<?php declare(strict_types=1);

use App\Entity\Language;
use App\Entity\Book;
use App\Entity\TextTag;
use App\DTO\BookDTO;
use PHPUnit\Framework\TestCase;

final class BookDTO_Test extends TestCase
{

    private function getDTO($eng): BookDTO {
        $d = new BookDTO();
        $d->language = $eng;
        $d->Title = "hi";
        $d->Text = "hello there";
        $d->SourceURI = "src";
        $d->bookTags = array();
        $tt = new TextTag();
        $tt->setText('tag');
        $d->bookTags[] = $tt;
        return $d;
    }

    public function test_smoke_createBook() {
        $eng = Language::makeEnglish();
        $d = $this->getDTO($eng);
        $b = $d->createBook();
        $this->assertEquals($b->getLanguage()->getLgName(), $eng->getLgName());
        $this->assertEquals($b->getTags()[0]->getText(), 'tag');
    }

    public function test_smoke_load_book_replaces_title_and_tags_only() {
        $fr = Language::makeFrench();
        $b = Book::makeBook('bonjour', $fr, 'Jai un chat.');
        $this->assertEquals('Jai un chat.', $b->getTexts()[0]->getText());
        $eng = Language::makeEnglish();
        $d = $this->getDTO($eng);

        $d->loadBook($b);
        $this->assertEquals($b->getLanguage()->getLgName(), $fr->getLgName(), 'still french');
        $this->assertEquals('Jai un chat.', $b->getTexts()[0]->getText(), 'same content');
        $this->assertEquals($b->getTags()[0]->getText(), 'tag', 'tag set');

        $this->assertTrue(true, 'todo');
    }

}