<?php declare(strict_types=1);

namespace App\Tests\acceptance;

class TermUpload_Test extends AcceptanceTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    ///////////////////////
    // Tests

    /**
     * @group acc_uploadterms
     */
    public function test_upload_terms_valid_file(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Import Terms');
        $ctx = $this->getTermUploadContext();

        $test_file = __DIR__ . '/Fixtures/term_import_1.csv';
        $ctx->uploadFile($test_file);

        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain(
            'two terms',
            [ '; gato; ; cat house cat; Spanish; animal, noun; New (1)',
              '; gatos; gato; ; Spanish; ; New (1)' ]);

        $this->client->clickLink('gato');
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Update')->form();
        $this->assertEquals($form['term_dto[Text]']->getValue(), 'gato', 'same term found');
        $this->assertEquals($form['term_dto[Translation]']->getValue(), "cat\nhouse cat", 'translation with return');
    }

    /**
     * @group acc_uploadterms
     */
    public function test_upload_terms_valid_file_variable_columns(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Import Terms');
        $ctx = $this->getTermUploadContext();

        $test_file = __DIR__ . '/Fixtures/term_import_variable_columns.csv';
        $ctx->uploadFile($test_file);

        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain(
            'two terms',
            [ '; gato; ; cat; Spanish; ; New (1)',
              '; gatos; gato; ; Spanish; ; New (1)' ]);
    }

    /**
     * @group acc_uploadterms_badfile
     */
    public function test_upload_terms_invalid_file(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Import Terms');
        $ctx = $this->getTermUploadContext();

        $test_file = __DIR__ . '/Fixtures/term_import_invalid_language.csv';
        $ctx->uploadFile($test_file);

        $this->assertSelectorTextContains('body', 'Unknown language "Esperanto"');
    }
    
}