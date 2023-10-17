<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Entity\Term;
use App\Domain\Dictionary;
use App\Domain\TermMappingService;
use App\Domain\TermService;

final class TermMappingService_Test extends DatabaseTestBase
{

    private TermMappingService $svc;
    private $mappings;

    public function childSetUp(): void
    {
        $this->load_languages();
        $this->svc = new TermMappingService(
            $this->term_repo
        );
    }

    private function assertParentEquals($t, $p, $msg = '') {
        $tlc = mb_strtolower($t);
        $p = $p ?? 'null';
        $plc = mb_strtolower($p);
        $sp = $this->spanish;
        $sql = "select cw.WoTextLC, ifnull(pw.WoTextLC, 'null')
from words cw
left outer join wordparents on WpWoID = cw.WoID
left outer join words pw on WpParentWoID = pw.WoID
where cw.WoTextLC = '{$tlc}'";
        // dump($sql);
        DbHelpers::assertTableContains($sql, [ "{$t}; {$plc}" ], "check " . $msg);
    }

    private function assertFlashMessageEquals($t, $m, $msg = '') {
        $sp = $this->spanish;
        $svc = new TermService($this->term_repo);
        $term = $svc->find($t, $sp);
        $this->assertEquals($term->getText(), $t, $t . ' found');
        $this->assertEquals($term->getFlashMessage(), $m, 'flash message ' . $msg);
    }

    private function assertStatsEquals($stats, $created, $updated) {
        $this->assertEquals($stats['created'], $created, 'created');
        $this->assertEquals($stats['updated'], $updated, 'updated');
    }

    private function assertTermsEquals($expected) {
        DbHelpers::assertTableContains('select wotext from words order by wotext', $expected);
    }

    public function assertMappingThrows($mappings) {
        $sp = $this->spanish;
        $threw = false;
        try {
            $stats = $this->svc->mapParents($sp, $this->language_repo, $mappings);
            dump($stats);
        }
        catch (\Exception $e) {
            $threw = true;
        }
        $this->assertTrue($threw);
    }

    public function test_throws_if_parent_or_child_blank_or_null() {  // V3-port: TODO
        foreach (['', ' ', null] as $p) {
            $this->assertMappingThrows([ [ 'parent' => 'gato', 'child' => $p ] ]);
            $this->assertMappingThrows([ [ 'parent' => $p, 'child' => 'gato' ] ]);
        }
    }

    private function doMappings($mappings) {
        $sp = $this->spanish;
        $stats = $this->svc->mapParents($sp, $this->language_repo, $mappings);
        return $stats;
    }

    /**
     * @group changetmapi
     */
    public function test_doesnt_create_terms() {  // V3-port: TODO
        $sp = $this->spanish;
        DbHelpers::assertRecordcountEquals('words', 0, 'no terms');
        $mappings = [
            [ 'parent' => 'gato', 'child' => 'gatos' ]
        ];
        $stats = $this->doMappings($mappings);
        DbHelpers::assertRecordcountEquals('words', 0, 'still no terms');
        $this->assertStatsEquals($stats, 0,0);
    }

    public function test__existing_term_no_parent__existing_parent__mapped() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['gato', 'gatos']);
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('gatos', 'gato', 'was mapped');
        $this->assertFlashMessageEquals('gatos', null, 'No flash message, would be annoying for many terms');
        $this->assertStatsEquals($stats, 0,1);
    }

    public function test__existing_term_no_parent__not_mapped_to_self() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['gato', 'gatos']);
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gato' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('gato', null, 'not mapped');
        $this->assertFlashMessageEquals('gato', null, 'No flash message, no change');
        $this->assertStatsEquals($stats, 0,0);
    }

    public function test__existing_term_no_parent__new_parent_created_and_mapped() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, 'gatos');
        $this->assertParentEquals('gatos', null);
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('gatos', 'gato');
        $this->assertFlashMessageEquals('gatos', null, 'No flash message, would be annoying for many terms');

        $this->assertParentEquals('gato', null);
        $this->assertFlashMessageEquals('gato', 'Auto-created parent for "gatos"');
        $this->assertStatsEquals($stats, 1,1);

        $this->assertTermsEquals(['gato', 'gatos']);
    }

    public function test__existing_term_no_parent__new_parent_created_and_mapped_multiple_children() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['gatos', 'gatoz']);
        $this->assertParentEquals('gatos', null);
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ],
            [ 'parent'=>'gato', 'child'=>'gatoz' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('gatos', 'gato');
        $this->assertFlashMessageEquals('gatos', null, 'No flash message, would be annoying for many terms');

        $this->assertParentEquals('gato', null);
        $this->assertFlashMessageEquals('gato', 'Auto-created parent for "gatos" + 1 more');
        $this->assertStatsEquals($stats, 1,2);

        $this->assertTermsEquals(['gato', 'gatos', 'gatoz']);
    }

    public function test__multiple_existing_term_no_parent__mapped_to_same_new_parent() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['gatos', 'gatitas']);
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ],
            [ 'parent'=>'gato', 'child'=>'gatitas' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('gatos', 'gato');
        $this->assertParentEquals('gatitas', 'gato');
        $this->assertStatsEquals($stats, 1,2);
        $this->assertTermsEquals(['gatitas', 'gato', 'gatos']);
    }

    public function test__existing_term_HAS_parent__parent_not_changed() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['gato', 'gatos', 'perro']);
        $svc = new TermService($this->term_repo);
        $term = $svc->find('gatos', $sp);
        $this->assertParentEquals('gatos', null);
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('gatos', 'gato');
        $this->assertFlashMessageEquals('gatos', null, 'No flash message');

        $mappings = [
            [ 'parent'=>'perro', 'child'=>'gatos' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('gatos', 'gato');
        $this->assertFlashMessageEquals('gatos', null, 'Still no flash message');
        $this->assertStatsEquals($stats, 0,0);
    }

    public function test_stray_term_skipped() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, 'gato');
        DbHelpers::assertRecordcountEquals('words', 1, 'just gato');
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ],
            [ 'parent'=>'blanco', 'child'=>'blancos' ]
        ];
        $stats = $this->doMappings($mappings);
        DbHelpers::assertRecordcountEquals('select wotext from words', 2);

        $this->assertParentEquals('gatos', 'gato');
        $this->assertFlashMessageEquals('gatos', 'Auto-created and mapped to parent "gato"', 'flash added');
        $this->assertStatsEquals($stats, 1,1);

        $this->assertTermsEquals(['gato', 'gatos']);
    }

    public function test_new_term_created_and_mapped_to_existing_parent() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, 'gato');
        $this->assertTermsEquals(['gato']);
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ]
        ];
        $stats = $this->doMappings($mappings);
        DbHelpers::assertRecordcountEquals('select wotext from words', 2, 'gatos created');

        $this->assertParentEquals('gatos', 'gato');
        $this->assertFlashMessageEquals('gatos', 'Auto-created and mapped to parent "gato"', 'Has flash message');
        $this->assertStatsEquals($stats, 1,1);
        $this->assertTermsEquals(['gato', 'gatos']);
    }

    /**
     * @group issue40
     * https://github.com/jzohrab/lute/issues/40
     */
    public function test_issue_40_multiple_parents_not_mapped() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['pA', 'pB', 'c']);
        $this->assertTermsEquals(['c', 'pA', 'pB']);
        $mappings = [
            [ 'parent'=>'pA', 'child'=>'c' ],
            [ 'parent'=>'pB', 'child'=>'c' ]
        ];
        $stats = $this->doMappings($mappings);
        DbHelpers::assertRecordcountEquals('wordparents', 0, 'no mappings');
    }

    /**
     * @group issue40
     * https://github.com/jzohrab/lute/issues/40
     */
    public function test_issue_40_dup_mapping_ok() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['pA', 'c']);
        $this->assertTermsEquals(['c', 'pA']);
        $mappings = [
            [ 'parent'=>'pA', 'child'=>'c' ],
            [ 'parent'=>'pA', 'child'=>'c' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('c', 'pa', 'parent mapped');
        DbHelpers::assertRecordcountEquals('select * from wordparents', 1, 'one mapping');
    }

    /**
     * @group issue40
     * https://github.com/jzohrab/lute/issues/40
     */
    public function test_issue_40_case_ignored_for_mapping() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['pA', 'Á']);
        $this->assertTermsEquals(['pA', 'Á'], 'initial settings');
        $mappings = [
            [ 'parent'=>'pA', 'child'=>'á' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('á', 'pa', 'mapped ok');
        DbHelpers::assertRecordcountEquals('select * from wordparents', 1, 'one mapping');
    }

    public function test_new_term__new_parent_not_created_if_not_otherwise_used() {  // V3-port: TODO
        $sp = $this->spanish;
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ]
        ];
        $stats = $this->doMappings($mappings);
        DbHelpers::assertRecordcountEquals('select wotext from words', 0, 'nothing created');
        $this->assertStatsEquals($stats, 0,0);
     }

    public function test_new_term__new_parent_created_if_used_by_existing_term() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, 'gatito');
        DbHelpers::assertTableContains('select wotext from words order by wotext', ['gatito']);
        $mappings = [
            [ 'parent'=>'gato', 'child'=>'gatos' ],
            [ 'parent'=>'gato', 'child'=>'gatito' ]
        ];
        $stats = $this->doMappings($mappings);
        $this->assertParentEquals('gatos', 'gato');
        $this->assertParentEquals('gatito', 'gato');
        DbHelpers::assertTableContains('select wotext from words order by wotext', ['gatito', 'gato', 'gatos']);
        $this->assertStatsEquals($stats, 2,2);
        $this->assertTermsEquals(['gatito', 'gato', 'gatos']);
     }

    /**
     * @group parentdependencies
     *
     * Tricky case where a new term will get created, but is also
     * needed as a parent for an existing term ... this showed up on
     * tests on my machine.
     */
    public function test_child_creates_new_parent_X_and_parent_creates_same_new_child_X() {  // V3-port: TODO
        $sp = $this->spanish;
        $p = $this->addTerms($sp, ['aladas', 'alado']);
        $mappings = [
            [ 'parent'=>'alada', 'child'=>'aladas' ],  // alada will get created here, because it's needed.
            [ 'parent'=>'alado', 'child'=>'alada' ]  // alada potentially also created here, child of alado.a
        ];
        $stats = $this->doMappings($mappings);
        $this->assertTermsEquals(['alada', 'aladas', 'alado']);

        $this->assertParentEquals('aladas', 'alada');
        $this->assertParentEquals('alada', 'alado');
    }

}
