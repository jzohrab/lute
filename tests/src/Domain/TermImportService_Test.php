<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Term;
use App\Domain\Dictionary;
use App\Domain\TermImportService;
use App\Domain\TermService;

/** NOTE: this fails for large import files when APP_ENV = TEST. */
final class TermImportService_Test extends DatabaseTestBase
{

    private TermImportService $svc;
    private $import;
    private $record;
    private $importfile;

    public function childSetUp(): void
    {
        $this->load_languages();
        $this->svc = new TermImportService(
            $this->language_repo,
            $this->term_repo,
            $this->termtag_repo
        );

        $sp = $this->spanish;
        $this->assertEquals($sp->getLgName(), 'Spanish', 'sanity check');
        DbHelpers::assertRecordcountEquals('words', 0, 'no terms');

        $this->record = [
            'language' => 'Spanish',
            'term' => 'gato',
            'translation' => 'cat',
            'parent' => '',
            'status' => 1,
            'tags' => 'animal, noun',
            'pronunciation' => 'GA-toh'
        ];

        $this->importfile = tempnam(sys_get_temp_dir(), "import.txt");
    }

    public function childTearDown(): void {
        unlink($this->importfile);
    }

    private function assertStatsEquals($stats, $created, $skipped) {
        $this->assertEquals($stats['created'], $created, 'created');
        $this->assertEquals($stats['skipped'], $skipped, 'skipped');
    }

    private function assertTermsEquals($expected, $msg = '') {
        DbHelpers::assertTableContains('select wotext from words order by wotext', $expected, $msg);
    }

    public function assertImportThrows($import, $expected_message = '') {
        $threw = false;
        try {
            $stats = $this->svc->doImport($import);
            // dump($stats);
        }
        catch (\Exception $e) {
            $threw = true;
            if ($expected_message != '') {
                $this->assertEquals($expected_message, $e->getMessage());
            }
        }
        $this->assertTrue($threw, "Should have thrown message " . $expected_message);
    }

    public function test_language_must_exist() {
        $this->record['language'] = 'UNKNOWN';
        $import = [ $this->record ];
        $this->assertImportThrows($import, 'Unknown language "UNKNOWN"');
    }

    public function test_dup_term_throws() {
        $import = [ $this->record, $this->record ];
        $this->assertImportThrows($import, 'Duplicate terms in import');
        $import = [
            $this->record,
            [ 'language' => 'Spanish',
              'term' => 'GATO',
              'translation' => '',
              'parent' => '',
              'status' => 1,
              'tags' => '',
              'pronunciation' => ''
            ]
        ];
        $this->assertImportThrows($import, 'Duplicate terms in import');
    }

    public function test_bad_status_throws() {
        $this->record['status'] = '7';
        $import = [ $this->record ];
        $this->assertImportThrows($import, 'Status must be one of 1,2,3,4,5,I,W, or blank');
    }

    public function test_term_required() {
        $this->record['term'] = '';
        $import = [ $this->record ];
        $this->assertImportThrows($import, 'Term is required');
    }

    private function doImport($import) {
        $stats = $this->svc->doImport($import);
        return $stats;
    }

    public function test_smoke_import_creates_term() {
        $import = [ $this->record ];
        $stats = $this->doImport($import);
        DbHelpers::assertRecordcountEquals('words', 1, '1 term');
        $this->assertTermsEquals(['gato'], 'created');
        $this->assertStatsEquals($stats, 1, 0);
    }

    public function test_smoke_import_two_terms() {
        $import = [
            $this->record,
            [
                'language' => 'Spanish',
                'term' => 'perro',
                'translation' => 'dog',
                'parent' => '',
                'status' => 1,
                'tags' => 'animal, noun',
                'pronunciation' => 'PERR-oh'
            ]
        ];
        $stats = $this->doImport($import);
        DbHelpers::assertRecordcountEquals('words', 2, '2 term');
        $this->assertTermsEquals(['gato', 'perro'], 'created');
        $this->assertStatsEquals($stats, 2, 0);
    }

    public function test_existing_terms_not_changed() {
        $t = new Term($this->spanish, 'gato');
        $t->setTranslation('hi there');
        $this->term_repo->save($t, true);
        $import = [ $this->record ];
        $stats = $this->doImport($import);
        DbHelpers::assertRecordcountEquals('words', 1, '1 term');
        $this->assertTermsEquals(['gato'], 'still exists');
        $this->assertStatsEquals($stats, 0, 1);

        $ts = new TermService($this->term_repo);
        $oldgato = $ts->find('gato', $this->spanish);
        $this->assertEquals($oldgato->getTranslation(), 'hi there', 'unchanged');
    }

    public function test_case_insens_term_creation() {
        $t = new Term($this->spanish, 'gato');
        $t->setTranslation('hi there');
        $this->term_repo->save($t, true);

        $this->record['term'] = 'GATO';
        $import = [ $this->record ];
        $stats = $this->doImport($import);
        DbHelpers::assertRecordcountEquals('words', 1, '1 term');
        $this->assertTermsEquals(['gato'], 'still exists');
        $this->assertStatsEquals($stats, 0, 1, 'was skipped');

        $ts = new TermService($this->term_repo);
        $oldgato = $ts->find('gato', $this->spanish);
        $this->assertEquals($oldgato->getTranslation(), 'hi there', 'unchanged');
    }

    public function test_statuses_set_correctly() {
        $cases = [ '' => 1, 'W' => 99, 'I' => 98, '3' => 3 ];
        foreach(array_keys($cases) as $k) {
            DbHelpers::exec_sql('delete from words');
            $this->record['status'] = $k;
            $import = [ $this->record ];
            $stats = $this->doImport($import);
            DbHelpers::assertTableContains('select wotext, wostatus from words', [ "gato; {$cases[$k]}" ], $k);
        }
    }

    /**
     * @group importparent
     */
    public function test_term_mapped_to_newly_created_parent() {
        $this->record['term'] = 'gatos';
        $this->record['parent'] = 'gato';
        $import = [ $this->record ];
        $stats = $this->doImport($import);
        $this->assertTermsEquals(['gato', 'gatos'], 'both created');
        $this->assertStatsEquals($stats, 2, 0, '2 created');

        $ts = new TermService($this->term_repo);
        $gato = $ts->find('gato', $this->spanish);
        $gatos = $ts->find('gatos', $this->spanish);

        $this->assertEquals($gatos->getParent()->getId(), $gato->getId(), 'mapped to gato');
        $this->assertEquals($gato->getFlashMessage(), 'Auto-created parent for "gatos"', 'gato implicitly created');
    }

    public function test_same_term_in_different_langs() {
        $import = [
            [ 'language' => 'Spanish',
              'term' => 'gatos',
              'translation' => '',
              'parent' => 'gato',
              'status' => 1,
              'tags' => '',
              'pronunciation' => ''
            ],
            [ 'language' => 'English',
              'term' => 'gato',
              'translation' => '',
              'parent' => '',
              'status' => 1,
              'tags' => '',
              'pronunciation' => ''
            ]
        ];

        $stats = $this->doImport($import);
        $this->assertTermsEquals(['gato', 'gato', 'gatos'], 'both created');
        $this->assertStatsEquals($stats, 3, 0, '3 created');

        $ts = new TermService($this->term_repo);
        $gato = $ts->find('gato', $this->spanish);
        $gatos = $ts->find('gatos', $this->spanish);
        $gato_eng = $ts->find('gato', $this->english);

        $this->assertEquals($gatos->getParent()->getId(), $gato->getId(), 'mapped to spanish gato');
        $this->assertTrue($gato_eng != null, 'have eng gato');
    }

    public function test_term_and_parent_imported() {
        $import = [
            [ 'language' => 'Spanish',
              'term' => 'gatos',
              'translation' => '',
              'parent' => 'gato',
              'status' => 1,
              'tags' => '',
              'pronunciation' => ''
            ],
            [ 'language' => 'Spanish',
              'term' => 'gato',
              'translation' => 'CAT',
              'parent' => '',
              'status' => 1,
              'tags' => '',
              'pronunciation' => ''
            ]
        ];

        $stats = $this->doImport($import);
        $this->assertTermsEquals(['gato', 'gatos'], 'both created');
        $this->assertStatsEquals($stats, 2, 0, '2 created');

        $ts = new TermService($this->term_repo);
        $gato = $ts->find('gato', $this->spanish);
        $gatos = $ts->find('gatos', $this->spanish);

        $this->assertEquals($gatos->getParent()->getId(), $gato->getId(), 'mapped');
        $this->assertEquals($gato->getTranslation(), 'CAT', 'x');
    }

    
    public function test_smoke_loadImportFile() {
        $tempfile = tempnam(sys_get_temp_dir(), "import.txt");
        $this->record['tags'] = 'tag';
        $data = array_values($this->record);
        $content = implode(",", $data);
        $headings = "language,term,translation,parent,status,tags,pronunciation";
        file_put_contents($tempfile, $headings . "\n" . $content);
        $actual = TermImportService::loadImportFile($tempfile);
        unlink($tempfile);
        $this->assertEquals([ $this->record ], $actual, 'same data struct returned');
    }

    /**
     * @group genimporttermfile
     */
    // HACK HACK HACK generate a test import file.
    public function zz_test_generate_import_file_1() {
        $data = [
            [
                'language' => 'Spanish',
                'term' => 'gato',
                'translation' => "A cat.
Or a really cool cat.",
                'parent' => '',
                'status' => 1,
                'tags' => 'animal, noun',
                'pronunciation' => 'GA-toh'
            ]
        ];

        $f = fopen($this->importfile, 'r+');
        foreach ($data as $rec)
            fputcsv($f, $rec);
        fclose($f);
        $csv_line = file_get_contents($this->importfile);
        dump($csv_line);
    }

    private function save_import_content($content) {
        file_put_contents($this->importfile, $content);
    }

    /**
     * @group importfile
     */
    public function test_import_file_with_return_in_translation() {
        $content = 'language,term,translation,parent,status,tags,pronunciation
Spanish,gato,"A cat.
A house cat.",,1,"animal, noun",GA-toh';

        $this->save_import_content($content);

        $stats = $this->svc->importFile($this->importfile);
        $this->assertStatsEquals($stats, 1, 0);
        $this->assertTermsEquals(['gato']);

        $ts = new TermService($this->term_repo);
        $gato = $ts->find('gato', $this->spanish);
        $this->assertEquals($gato->getTranslation(), "A cat.\nA house cat.", "has slash n");
    }

    /**
     * @group importfile
     */
    public function test_import_more_stuff() {
        $content = 'language,term,translation,parent,status,tags,pronunciation
Spanish,term,"this is a trans
1. something
2. ok",,1,"a, b",
Spanish,other,"another thing:
1. blah
2. ""you know""",,3,,TEE-2
Spanish,third,,,W,?,';
        $this->save_import_content($content);

        $stats = $this->svc->importFile($this->importfile);
        $this->assertStatsEquals($stats, 3, 0);
        $this->assertTermsEquals(['other', 'term', 'third']);

        $ts = new TermService($this->term_repo);
        $tx = $ts->find('other', $this->spanish);
        $t2xl = "another thing:
1. blah
2. \"you know\"";
        $this->assertEquals($tx->getTranslation(), $t2xl, "check");
    }

    /**
     * @group importfile
     */
    public function test_fields_can_be_in_different_order() {
        $content = 'language,translation,term,parent,status,tags,pronunciation
Spanish,t1 trans,term,,1,"a, b",
Spanish,o1 trans,other,,3,,TEE-2
Spanish,3 trans,third,,W,?,';
        $this->save_import_content($content);

        $stats = $this->svc->importFile($this->importfile);
        $this->assertStatsEquals($stats, 3, 0);
        $this->assertTermsEquals(['other', 'term', 'third']);

        $ts = new TermService($this->term_repo);
        $tx = $ts->find('other', $this->spanish);
        $t2xl = 'o1 trans';
        $this->assertEquals($tx->getTranslation(), $t2xl, "check");
    }

    /**
     * @group importcols
     */
    public function test_partial_columns_are_allowd() {
        $this->record = [
            'language' => 'Spanish',
            'term' => 'gato',
            'translation' => 'cat'
        ];
        $import = [ $this->record ];
        $stats = $this->doImport($import);
        $this->assertTermsEquals(['gato'], 'created');
        $this->assertStatsEquals($stats, 1, 0, '2 created');

        $ts = new TermService($this->term_repo);
        $gato = $ts->find('gato', $this->spanish);

        $this->assertEquals($gato->getTranslation(), 'cat', 'translation set');
        $this->assertEquals($gato->getStatus(), 1, 'default = 1');
    }

    /**
     * @group importcols
     */
    public function test_language_and_term_required() {
        $this->record = [
            'language' => 'Spanish',
            'termx' => 'gato',
        ];

        $import = [ $this->record ];
        $this->assertImportThrows($import, 'Missing required field "term"');
    }

    /**
     * @group importcols
     */
    public function test_only_language_and_term_is_ok() {
        $this->record = [
            'language' => 'Spanish',
            'term' => 'gato',
        ];
        $import = [ $this->record ];
        $stats = $this->doImport($import);
        $this->assertTermsEquals(['gato'], 'created');
        $this->assertStatsEquals($stats, 1, 0, '2 created');

        $ts = new TermService($this->term_repo);
        $gato = $ts->find('gato', $this->spanish);

        $this->assertEquals($gato->getTranslation(), '', 'empty translation set');
        $this->assertEquals($gato->getStatus(), 1, 'default = 1');
    }

    /**
     * @group importcols
     */
    public function test_bad_headings_throws() {
        $this->record = [
            'language' => 'Spanish',
            'term' => 'gato',
            'badfield' => 'junk'
        ];
        $import = [ $this->record ];
        $this->assertImportThrows($import, 'Unknown field "badfield"');
    }

    /**
     * @group importcols
     */
    public function test_import_file_must_contain_language_and_heading() {
        $content = 'language,xterm
Spanish,term';
        $this->save_import_content($content);

        $threw = false;
        try {
            $stats = $this->svc->importFile($this->importfile);
        }
        catch (\Exception $e) {
            $threw = true;
            $this->assertEquals('Missing required field "term"', $e->getMessage());
        }
        $this->assertTrue($threw);
    }

    /**
     * @group importcols_1
     */
    public function test_bad_headings_in_file_throws() {
        $content = 'language,term,badfield
Spanish,term,junk';
        $this->save_import_content($content);

        $threw = false;
        try {
            $stats = $this->svc->importFile($this->importfile);
        }
        catch (\Exception $e) {
            $threw = true;
            $this->assertEquals('Unknown field "badfield"', $e->getMessage());
        }
        $this->assertTrue($threw);
    }

}
