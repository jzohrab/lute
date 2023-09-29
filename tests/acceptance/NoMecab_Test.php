<?php declare(strict_types=1);

namespace App\Tests\acceptance;

require_once __DIR__ . '/../db_helpers.php';

use App\Domain\JapaneseParser;
use App\Utils\SqliteHelper;

class NoMecab_Test extends AcceptanceTestBase
{

    /**
     * @group nomecab
     *
     * This test checks if a computer without MeCab still loads the
     * demo db ok.
     */
    public function test_no_mecab_should_still_be_ok(): void
    {
        if (JapaneseParser::MeCab_installed()) {
            $this->markTestSkipped('Skipping test, MeCab is installed.');
        }

        // Restore the baseline db -- the AcceptanceTestBase wipes the db,
        // but for this test we want to pretend that it's a brand new install.
        SqliteHelper::CreateDb();
        
        $this->client->request('GET', '/');

        $this->assertPageTitleContains('LUTE');
        $this->assertSelectorTextContains('body', 'Learning Using Texts (LUTE)');
        $this->assertSelectorTextContains('body', 'Tutorial');
        $this->assertSelectorTextContains('body', 'Aladino y la lámpara maravillosa');

        // No Japanese!
        $this->assertSelectorTextNotContains('body', '北風と太陽 - きたかぜたいよう');
    }

}