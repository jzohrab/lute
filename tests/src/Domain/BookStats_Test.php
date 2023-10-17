<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Book;
use App\Domain\BookStats;

final class BookStats_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    private function do_refresh() {
        BookStats::refresh($this->book_repo, $this->term_service);
    }

    public function test_cache_loads_when_prompted()  // V3-port: TODO
    {
        DbHelpers::assertRecordcountEquals("bookstats", 0, "nothing loaded");

        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);

        DbHelpers::assertRecordcountEquals("bookstats", 0, "still not loaded");

        $this->do_refresh();
        DbHelpers::assertRecordcountEquals("bookstats", 1, "loaded");
    }

    public function test_stats_smoke_test() {  // V3-port: TODO
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $b = $t->getBook();
        $this->addTerms($this->spanish, [
            "gato", "TENGO"
        ]);
        $this->do_refresh();

        $sql = "select 
          BkID, wordcount, distinctterms,
          distinctunknowns, unknownpercent
          from bookstats";
        DbHelpers::assertTableContains(
            $sql,
            [ "{$b->getId()}; 4; 4; 2; 50" ]);
    }

    /**
     * @group issue55
     * If multiterms "cover" the existing text, then it's really fully known.
     */
    public function test_stats_calculates_rendered_text() {  // V3-port: TODO
        $t = $this->make_text("Hola.", "Tengo un gato.", $this->spanish);
        $b = $t->getBook();
        $this->addTerms($this->spanish, [ "tengo un" ]);
        $this->do_refresh();

        $sql = "select 
          BkID, wordcount, distinctterms,
          distinctunknowns, unknownpercent
          from bookstats";
        DbHelpers::assertTableContains(
            $sql,
            [ "{$b->getId()}; 3; 2; 1; 50" ],
            "2 terms: 'tengo un' and 'gato'"
        );
    }

    public function test_stats_only_update_existing_books_if_specified() {  // V3-port: TODO
        $t = $this->make_text("Hola.", "Hola tengo un gato.", $this->spanish);
        $b = $t->getBook();
        $this->addTerms($this->spanish, [
            "gato", "TENGO"
        ]);
        $this->do_refresh();

        $sql = "select 
          BkID, wordcount, distinctterms,
          distinctunknowns, unknownpercent
          from bookstats";
        DbHelpers::assertTableContains(
            $sql,
            [ "{$b->getId()}; 4; 4; 2; 50" ]);

        $this->addTerms($this->spanish, [
            "hola"
        ]);
        $this->do_refresh();
        DbHelpers::assertTableContains(
            $sql,
            [ "{$b->getId()}; 4; 4; 2; 50" ],
            "not updated yet"
        );

        BookStats::markStale($b);
        $this->do_refresh();
        DbHelpers::assertTableContains(
            $sql,
            [ "{$b->getId()}; 4; 4; 1; 25" ],
            "now updated, after marked stale"
        );
    }
        
}
