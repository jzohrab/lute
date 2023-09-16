<?php declare(strict_types=1);

namespace App\Tests\acceptance;

use App\Entity\Language;

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
        $form = $crawler->selectButton('Save')->form();
        $this->assertEquals($form['term_dto[Text]']->getValue(), 'gato', 'same term found');
        $this->assertEquals($form['term_dto[Translation]']->getValue(), "cat\nhouse cat", 'translation with return');
    }

    /**
     * @group acc_uploadterms_varcols
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

    /**
     * @group issue50
     * valid file rejected
     */
    public function test_issue_50(): void
    {
        $cc = Language::makeClassicalChinese();
        $cc->setLgName('Chinese'); // used in the import file.
        $this->language_repo->save($cc, true);

        $this->client->request('GET', '/');
        $this->client->clickLink('Import Terms');
        $ctx = $this->getTermUploadContext();

        $test_file = __DIR__ . '/Fixtures/term_import_issue_50_hsk.csv';
        $ctx->uploadFile($test_file);

        $this->assertSelectorTextContains('body', 'Learning Using Texts (LUTE)');
        $this->client->clickLink('Terms');
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain(
            'two terms imported',
            [ '; 爱; ; love; Chinese; HSK1; New (1)',
              '; 爱好; ; hobby; Chinese; HSK1; New (1)' ]);
    }

}