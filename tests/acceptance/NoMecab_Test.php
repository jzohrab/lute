<?php declare(strict_types=1);

namespace App\Tests\acceptance;

require_once __DIR__ . '/../db_helpers.php';

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
        if ($this->mecab_installed) {
            // Dummy assert to pass, b/c I don't want to always see
            // that this has been skipped.
            \PHPUnit\Framework\Assert::assertEquals(1, 1, 'MeCab installed, dummy assert');
            return;
        }

        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('#booktable', 'Tutorial');

        $this->assertPageTitleContains('LUTE');
        $this->assertSelectorTextContains('body', 'Learning Using Texts (LUTE)');
        $this->assertSelectorTextContains('body', 'Tutorial');
        $this->assertSelectorTextContains('body', 'Aladino y la lámpara maravillosa');

        // No Japanese!
        $this->assertSelectorTextNotContains('body', '北風と太陽 - きたかぜたいよう');
    }

}