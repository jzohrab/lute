<?php declare(strict_types=1);

namespace App\Tests\acceptance;

class TermUpload_Test extends AcceptanceTestBase
{

    ///////////////////////
    // Tests

    /**
     * @group acc_uploadterms
     */
    public function test_upload_terms_valid_file(): void  // V3-port: DONE
    {
        $wait = function() { usleep(200 * 1000); };  // hack
        $this->client->request('GET', '/');
        $this->client->clickLink('Import Terms');
        $ctx = $this->getTermUploadContext();

        $test_file = __DIR__ . '/Fixtures/term_import_1.csv';
        $ctx->uploadFile($test_file);
        $wait();

        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $wait();
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain(
            'two terms',
            [ '; gato; ; cat house cat; Spanish; animal, noun; New (1)',
              '; gatos; gato; ; Spanish; ; New (1)' ]);

        $this->client->clickLink('gato');
        $wait();
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Save')->form();
        $this->assertEquals($form['term_dto[Text]']->getValue(), 'gato', 'same term found');
        $this->assertEquals($form['term_dto[Translation]']->getValue(), "cat\nhouse cat", 'translation with return');
    }

    /**
     * @group acc_uploadterms_varcols
     */
    public function test_upload_terms_valid_file_variable_columns(): void  // V3-port: DONE - skipping, redundant
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Import Terms');
        $wait = function() { usleep(200 * 1000); };  // hack
        $wait();
        $ctx = $this->getTermUploadContext();

        $test_file = __DIR__ . '/Fixtures/term_import_variable_columns.csv';
        $ctx->uploadFile($test_file);
        $wait();

        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $wait();
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain(
            'two terms',
            [ '; gato; ; cat; Spanish; ; New (1)',
              '; gatos; gato; ; Spanish; ; New (1)' ]);
    }

    /**
     * @group acc_uploadterms_badfile
     */
    public function test_upload_terms_invalid_file(): void  // V3-port: DONE - skipping, redundant
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Import Terms');
        $ctx = $this->getTermUploadContext();

        $test_file = __DIR__ . '/Fixtures/term_import_invalid_language.csv';
        $ctx->uploadFile($test_file);

        $this->assertSelectorTextContains('body', 'Unknown language "Esperanto"');
    }

    /**
     * @group issue50
     * valid file rejected
     */
    public function test_issue_50(): void  // V3-port: DONE
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Import Terms');
        $wait = function() { usleep(200 * 1000); };  // hack
        $wait();
        $ctx = $this->getTermUploadContext();

        $test_file = __DIR__ . '/Fixtures/term_import_issue_50_hsk.csv';
        $ctx->uploadFile($test_file);
        $wait();

        $this->assertSelectorTextContains('body', 'Learning Using Texts (LUTE)');
        $this->client->clickLink('Terms');
        $wait();
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain(
            'two terms imported',
            [ '; 爱; ; love; Classical Chinese; HSK1; New (1)',
              '; 爱好; ; hobby; Classical Chinese; HSK1; New (1)' ]);
    }

}