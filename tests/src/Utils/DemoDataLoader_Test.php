<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\DemoDataLoader;
use App\Domain\TermService;


final class DemoDataLoader_Test extends DatabaseTestBase
{

    /**
     * @group loadyaml
     */
    public function test_can_load_from_yaml() {
        $booksql = "select BkTitle, LgName
from books
inner join languages on lgid = bklgid
";
        $langsql = "select LgName from languages";
        DbHelpers::assertTableContains($langsql, []);
        DbHelpers::assertTableContains($booksql, []);

        $term_svc = new TermService($this->term_repo);
        $ddl = new DemoDataLoader($this->language_repo, $this->book_repo, $term_svc);
        $ddl->loadDemoFile('arabic.yaml');

        DbHelpers::assertTableContains($langsql, [ 'Arabic' ], 'Arabic loaded');
        DbHelpers::assertTableContains($booksql, [ 'Examples; Arabic' ], 'Example arabic loaded');
    }

    // TODO: add loadAllDemoFiles
}
