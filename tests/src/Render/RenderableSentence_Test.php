<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/Domain/ReadingFacade.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Render\RenderableSentence;
use App\Entity\Text;
use App\Entity\Book;
use App\Entity\Language;

final class Renderablesentence_Test extends DatabaseTestBase
{

    private ReadingFacade $facade;
    private int $spid;

    public function childSetUp(): void
    {
        $this->load_languages();
        $this->spid = $this->spanish->getLgID();
    }

    // TESTS -----------------

    /**
     * @group rendsent
     */
    public function test_smoke_get_paras()  // V3-port: TODO
    {
        [ $t1, $t2 ] = $this->addTerms($this->spanish, [ 'tengo un', 'un gato' ]);

        $content = "Tengo un gato. Hay un perro.\nTengo un perro.";
        $t = $this->make_text("Hola", $content, $this->spanish);

        $paras = RenderableSentence::getParagraphs($t, $this->term_service);
        $this->assertEquals(count($paras), 2, '2 paragraphs.');

        $stringize = function($t) {
            $zws = mb_chr(0x200B);
            $parts = [
                '[',
                "'" . str_replace($zws, '|', $t->DisplayText) . "'",
                'p' . $t->ParaID,
                's' . $t->SeID,
                ']'
            ];
            return implode(' ', $parts);
        };

        $sentences = array_merge([], ...$paras);
        $actual = [];
        foreach ($sentences as $sent)
            $actual[] = implode(', ', array_map($stringize, $sent->renderable()));

        $expected = [
            "[ 'Tengo| |un' p0 s0 ], [ ' |gato' p0 s0 ], [ '. ' p0 s0 ]",
            "[ 'Hay' p0 s1 ], [ ' ' p0 s1 ], [ 'un' p0 s1 ], [ ' ' p0 s1 ], [ 'perro' p0 s1 ], [ '.' p0 s1 ]",
            "[ 'Tengo| |un' p1 s3 ], [ ' ' p1 s3 ], [ 'perro' p1 s3 ], [ '.' p1 s3 ]"
        ];
        $this->assertEquals($actual, $expected);
    }

}
