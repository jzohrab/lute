<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Term;
use App\Domain\Dictionary;
use App\Domain\TermMappingService;

final class TermMappingService_File_Test extends DatabaseTestBase
{

    private TermMappingService $svc;
    private ?string $tempfile = null;

    public function childSetUp(): void
    {
        $this->load_languages();
        $this->svc = new TermMappingService(
            $this->term_repo
        );
    }

    public function childTearDown(): void
    {
        if ($this->tempfile != null)
            unlink($this->tempfile);
    }
    
    public function test_smoke_language_file_created() {  // V3-port: DONE
        $this->addTerms($this->spanish, [ 'gato', 'lista', 'tiene una', 'listo' ]);
        $content = "Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.";
        $t = $this->make_text('Hola', $content, $this->spanish);

        $expected = [
            '',  // Don't know why this is there, don't care.
            'gato',
            'lista',
            'listo'
        ];
        sort($expected);

        $this->tempfile = tempnam(sys_get_temp_dir(), "lute");
        $f = $this->tempfile;
        $this->svc->lemma_export_language($this->spanish, $f);
        $c = file_get_contents($f);
        $actual = explode(PHP_EOL, $c);
        sort($actual);
        $this->assertEquals($expected, $actual, "contents");
    }

    public function test_smoke_book_file_created() {  // V3-port: DONE
        $this->addTerms($this->spanish, [ 'gato', 'lista', 'tiene una', 'listo' ]);
        $content = "Hola tengo un gato.  No tengo una lista.\nElla tiene una bebida.";
        $t = $this->make_text('Hola', $content, $this->spanish);

        $expected = [
            '',  // Don't know why this is there, don't care.
            'hola',
            'tengo',
            'un',
            'no',
            'una',
            'ella',
            'tiene',
            'bebida'
        ];
        sort($expected);

        $this->tempfile = tempnam(sys_get_temp_dir(), "lute");
        $f = $this->tempfile;
        $this->svc->lemma_export_book($t->getBook(), $f);
        $c = file_get_contents($f);
        $actual = explode(PHP_EOL, $c);
        sort($actual);
        $this->assertEquals($expected, $actual, "contents");
    }

    public function test_smoke_import_file_to_array()  // V3-port: DONE - skipping
    {
        $this->tempfile = tempnam(sys_get_temp_dir(), "lute");
        $f = $this->tempfile;
        $c = "
parent\tchild
good\tline
badnotabs

perrito  spaceignored

# hash\tignored
another\tgoodline
bad\tmultiple\tabs
\tjunkhere
parentmissingchild\t
\tchildmissingparent
\t";
        file_put_contents($f, $c);
        $mappings = TermMappingService::loadMappingFile($f);
        // Only good lines are included, the rest are ignored.
        $expected = [
            [ 'parent'=>'parent', 'child'=>'child' ],
            [ 'parent'=>'good', 'child'=>'line' ],
            [ 'parent'=>'another', 'child'=>'goodline' ]
        ];
        $this->assertEquals($mappings, $expected);
    }
}
