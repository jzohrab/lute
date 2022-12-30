<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Domain\Dictionary;
use App\Entity\Term;

final class Dictionary_Test extends DatabaseTestBase
{

    private Dictionary $dictionary;
    private Term $p;
    private Term $p2;

    public function childSetUp(): void
    {
        $this->load_languages();

        $this->dictionary = new Dictionary(
            $this->entity_manager,
            $this->term_repo
        );

        $p = new Term();
        $p->setLanguage($this->spanish);
        $p->setText("PARENT");
        $p->setStatus(1);
        $p->setWordCount(1);
        $this->term_repo->save($p, true);
        $this->p = $p;

        $p2 = new Term();
        $p2->setLanguage($this->spanish);
        $p2->setText("OTHER");
        $p2->setStatus(1);
        $p2->setWordCount(1);
        $this->term_repo->save($p2, true);
        $this->p2 = $p2;
    }

    public function test_find_by_text_is_found()
    {
        $cases = [ 'PARENT', 'parent', 'pAReNt' ];
        foreach ($cases as $c) {
            $p = $this->dictionary->find($c, $this->spanish);
            $this->assertTrue(! is_null($p), 'parent found for case ' . $c);
            $this->assertEquals($p->getText(), 'PARENT', 'parent found for case ' . $c);
        }
    }

    // TESTS:
    // find term
    // find term match
    // add term saves
    // remove term removes

    // TODO:move
    // search for findTermInLanguage, point to dict
    // remove TermRepository find tests
    // remove findTermInLanguage
    
}
