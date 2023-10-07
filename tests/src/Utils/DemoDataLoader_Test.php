<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\DemoDataLoader;
use App\Domain\TermService;
use App\Parse\JapaneseParser;


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

        $demodir = dirname(__FILE__) . '/../../../demo/languages/';
        $f = $demodir . 'arabic.yaml';
        $ddl->loadDemoLanguage($f);
        $ddl->loadDemoStories();

        DbHelpers::assertTableContains($langsql, [ 'Arabic' ], 'Arabic loaded');
        DbHelpers::assertTableContains($booksql, [ 'Examples; Arabic' ], 'Example arabic loaded');
    }

    /**
     * @group loadyaml
     */
    public function test_can_load_all_yaml() {
        if (!JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, missing MeCab.');
        }

        $booksql = "select LgName, BkTitle
from books
inner join languages on lgid = bklgid
order by LgName, BkTitle
";
        DbHelpers::assertTableContains($booksql, []);

        $term_svc = new TermService($this->term_repo);
        DemoDataLoader::loadDemoData($this->language_repo, $this->book_repo, $term_svc);

        $expected = [
            'Arabic; Examples',
            'Classical Chinese; 逍遙遊',
            'English; Tutorial',
            'English; Tutorial follow-up',
            'French; Boucles d’or et les trois ours',
            'German; Die Bremer Stadtmusikanten',
            'Greek; Γεια σου, Νίκη. Ο Πέτρος είμαι.',
            'Japanese; 北風と太陽 - きたかぜたいよう',
            'Spanish; Aladino y la lámpara maravillosa',
            'Turkish; Büyük ağaç',
        ];
        DbHelpers::assertTableContains($booksql, $expected);
    }

}
