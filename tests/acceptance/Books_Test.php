<?php declare(strict_types=1);

namespace App\Tests\acceptance;

use App\Entity\Status;

class Books_Test extends AcceptanceTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    ///////////////////////
    // Tests

    /**
     * @group smoketestbook
     */
    public function test_create_book(): void
    {
        $this->client->request('GET', '/');
        $crawler = $this->client->refreshCrawler();
        // Have to filter or the "create new text" link isn't shown.
        $crawler->filter("input")->sendKeys('Hola');
        $this->client->clickLink('Create new Text');

        $ctx = $this->getBookContext();
        $updates = [
            'language' => $this->spanish->getLgID(),
            'Title' => 'Hola',
            'Text' => 'Hola. Tengo un gato.',
        ];
        $ctx->updateBookForm($updates);
        $this->client->waitForElementToContain('body', 'Tengo');
        $ctx = $this->getReadingContext();
        $ctx->assertDisplayedTextEquals('Hola/. /Tengo/ /un/ /gato/.', 'book content shown');

        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', 'Hola');

        $crawler = $this->client->refreshCrawler();
        // Filter so that only the one row is shown ...
        // have to wait for filter to take effect!
        $crawler->filter("input")->sendKeys('Hola');
        usleep(1000 * 1000); // 1 sec
        $ctx = $this->getBookContext();
        $ctx->listingShouldContain('Book shown', [ 'Hola; Spanish; ; 4 (0%); ' ]);
    }
    
}