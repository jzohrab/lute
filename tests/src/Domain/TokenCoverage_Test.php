<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Book;
use App\Entity\Term;
use App\Domain\BookStats;
use App\Domain\ReadingFacade;
use App\Domain\TokenCoverage;

final class TokenCoverage_Test extends DatabaseTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    public function addTerm(string $s, int $status) {
        $term = new Term($this->spanish, $s);
        $term->setStatus($status);
        $this->term_service->add($term, true);
    }

    public function scenario(string $fulltext, $terms_and_statuses, $expected) {
        $t = $this->make_text("Hola.", $fulltext, $this->spanish);
        $b = $t->getBook();

        foreach ($terms_and_statuses as $ts)
            $this->addTerm($ts[0], $ts[1]);

        $tc = new TokenCoverage();
        $stats = $tc->getStats($b, $this->term_service);

        $this->assertEquals($stats, $expected);
    }

    public function test_two_words() {  // V3-port: DONE test book stats
        $this->scenario("Tengo un gato.  Tengo un perro.",
                        [[ "gato", 1 ], [ "perro", 2 ]],
                        [
                            0 => 2,
                            1 => 1,
                            2 => 1,
                            3 => 0,
                            4 => 0,
                            5 => 0,
                            98 => 0,
                            99 => 0
                        ]
        );
    }

    public function test_single_word() {  // V3-port: DONE
        $this->scenario("Tengo un gato.  Tengo un perro.",
                        [[ "gato", 3 ]],
                        [
                            0 => 3,
                            1 => 0,
                            2 => 0,
                            3 => 1,
                            4 => 0,
                            5 => 0,
                            98 => 0,
                            99 => 0
                        ]
        );
    }

    public function test_with_multiword() {  // V3-port: DONE
        $this->scenario("Tengo un gato.  Tengo un perro.",
                        [[ "tengo un", 3 ]],
                        [
                            0 => 2,
                            1 => 0,
                            2 => 0,
                            3 => 1,
                            4 => 0,
                            5 => 0,
                            98 => 0,
                            99 => 0
                        ]
        );
    }

    public function test_chinese_stats() {  // V3-port: DONE
        $t = $this->make_text('Hola.', '這是東西', $this->classicalchinese);
        $b = $t->getBook();

        $tc = new TokenCoverage();
        $stats = $tc->getStats($b, $this->term_service);
        $expected = [
            0 => 4,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            98 => 0,
            99 => 0
        ];
        $this->assertEquals($stats, $expected, '4 chars');

        $term = new Term($this->classicalchinese, '東西');
        $term->setStatus(1);
        $this->term_service->add($term, true);

        $tc = new TokenCoverage();
        $stats = $tc->getStats($b, $this->term_service);
        $expected = [
            0 => 2,
            1 => 1,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            98 => 0,
            99 => 0
        ];
        $this->assertEquals($stats, $expected, '2 unks, 1 word');

    }

}
