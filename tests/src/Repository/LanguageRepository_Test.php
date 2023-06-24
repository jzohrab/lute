<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Language;
use App\Entity\Term;
use App\Entity\Book;
use App\Domain\TermService;

final class LanguageRepository_Test extends DatabaseTestBase
{

    public function test_can_delete_language_even_if_terms_and_books_are_defined()
    {
        $english = Language::makeEnglish();
        $this->language_repo->save($english, true);

        $b = Book::makeBook('test', $english, 'Here is some text.');
        $this->book_repo->save($b, true);

        $term_service = new TermService($this->term_repo);
        $t1 = new Term($english, "Hello");
        $t2 = new Term($english, "there");
        $t1->setParent($t2);
        $term_service->add($t1, true);

        DbHelpers::assertRecordcountEquals('select * from languages', 1, '1 lang');
        DbHelpers::assertRecordcountEquals('select * from books', 1, '1 book');
        DbHelpers::assertRecordcountEquals('select * from words', 2, '2 terms');
        DbHelpers::assertRecordcountEquals('select * from wordparents', 1, '1 parent');

        $this->language_repo->remove($english, true);
        $tables = [ 'languages', 'books', 'bookstats', 'texts', 'words', 'wordparents', 'texttokens' ];
        foreach ($tables as $t) {
            DbHelpers::assertRecordcountEquals('select * from ' . $t, 0, $t . ' deleted');
        }
    }

}
