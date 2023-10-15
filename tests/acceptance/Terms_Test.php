<?php declare(strict_types=1);

namespace App\Tests\acceptance;

use App\Entity\Status;

class Terms_Test extends AcceptanceTestBase
{

    ///////////////////////
    // Tests

    public function test_term_table_empty(): void
    {
        $this->client->request('GET', '/');
        $wait = function() { usleep(200 * 1000); };  // hack
        $this->client->clickLink('Terms');
        $wait();
        $ctx = $this->getTermContext();
        $ctx->listingShouldContain('no data', [ 'No data available in table' ]);
    }


    public function test_single_term_created(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $this->client->clickLink('Create new');

        $ctx = $this->getTermContext();
        $updates = [
            'language' => $this->spanishid,
            'Text' => 'gato',
            'Translation' => 'cat'
        ];
        $ctx->updateTermForm($updates);

        $ctx->listingShouldContain('one term', [ '; gato; ; cat; Spanish; ; New (1)' ]);

        $this->client->clickLink('gato');
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Save')->form();
        $this->assertEquals($form['term_dto[Text]']->getValue(), 'gato', 'same term found');
    }


    /**
     * @group tandp
     */
    public function test_term_and_parent_created(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $this->client->clickLink('Create new');

        $updates = [
            'language' => $this->spanishid,
            'Text' => 'gatos',
            'Parents' => ['gato'],
            'Translation' => 'cat'
        ];
        $ctx = $this->getTermContext();
        $ctx->updateTermForm($updates);

        $expected = [
            '; gato; ; cat; Spanish; ; New (1)',
            '; gatos; gato; cat; Spanish; ; New (1)',
        ];
        $ctx->listingShouldContain('2 terms', $expected);

        $this->client->clickLink('gatos');
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Save')->form();
        $this->assertEquals($form['term_dto[Text]']->getValue(), 'gatos', 'same term found');
    }

    /**
     * @group termandmultipleparents
     */
    public function test_term_and_multiple_parents_created(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $this->client->clickLink('Create new');

        $updates = [
            'language' => $this->spanishid,
            'Text' => 'aaaa',
            'Parents' => ['aa', 'bb'],
            'Translation' => 'thing'
        ];
        $ctx = $this->getTermContext();
        $ctx->updateTermForm($updates);

        $expected = [
            '; aa; ; thing; Spanish; ; New (1)',
            '; aaaa; aa, bb; thing; Spanish; ; New (1)',
            '; bb; ; thing; Spanish; ; New (1)',
        ];
        $ctx->listingShouldContain('3 terms', $expected);
    }

    /**
     * @group termlistfilters
     */
    public function test_term_list_filters(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $ctx = $this->getTermContext();
        $ctx->assertFilterVisibilityIs(false, 'Initially not visible');

        $this->client->clickLink('Create new');
        $updates = [
            'language' => $this->spanishid,
            'Text' => 'gatos',
            'Parents' => ['gato'],
            'Translation' => 'cat'
        ];
        $ctx->updateTermForm($updates);

        $ctx->listingShouldContain('Initial data',
            [
                '; gato; ; cat; Spanish; ; New (1)',
                '; gatos; gato; cat; Spanish; ; New (1)',
            ]
        );

        $ctx->assertFilterVisibilityIs(false, "Filter not shown");

        $this->client->getMouse()->clickTo("#showHideFilters");
        $ctx->assertFilterVisibilityIs(true, "Filter shown");

        $this->client->getMouse()->clickTo("#filtParentsOnly");
        usleep(300 * 1000);
        $ctx->listingShouldContain(
            'Only parent shown',
            [
                '; gato; ; cat; Spanish; ; New (1)'
            ]
        );

        $this->client->getMouse()->clickTo("#showHideFilters");
        usleep(300 * 1000);
        $ctx->assertFilterVisibilityIs(false, "Filter not shown after hide");
        $ctx->listingShouldContain(
            'All data shown again',
            [
                '; gato; ; cat; Spanish; ; New (1)',
                '; gatos; gato; cat; Spanish; ; New (1)',
            ]
        );

        $this->client->clickLink('gatos');
        $ctx->updateTermForm(['Status' => Status::IGNORED]);
        usleep(300 * 1000);
        $ctx->listingShouldContain(
            'Ignored term not included',
            [
                '; gato; ; cat; Spanish; ; New (1)'
            ]
        );

        $this->client->getMouse()->clickTo("#showHideFilters");
        $ctx->assertFilterVisibilityIs(true, "Filter now shown");
        $this->client->getMouse()->clickTo("#filtIncludeIgnored");
        usleep(300 * 1000);
        $ctx->listingShouldContain(
            'Ignored term now included',
            [
                '; gato; ; cat; Spanish; ; New (1)',
                '; gatos; gato; cat; Spanish; ; Ignored'
            ]
        );

        $this->client->clickLink('gatos');
        $ctx->updateTermForm([ 'Status' => 2 ]);
        usleep(300 * 1000);
        $ctx->assertFilterVisibilityIs(true, "Filter is still shown, was never hidden");
        $ctx->listingShouldContain(
            'Previously ignored term still shown',
            [
                '; gato; ; cat; Spanish; ; New (1)',
                '; gatos; gato; cat; Spanish; ; New (2)'
            ]
        );

        $crawler = $this->client->refreshCrawler();
        $myInput = $crawler->filterXPath(".//select[@id='filtStatusMin']//option[@value='2']");
        $myInput->click();
        usleep(300 * 1000);
        $ctx->listingShouldContain(
            'Min status set for filter',
            [
                '; gatos; gato; cat; Spanish; ; New (2)'
            ]
        );

        $crawler = $this->client->refreshCrawler();
        $crawler->filter("#filtAgeMin")->sendKeys('7');
        usleep(300 * 1000);
        $ctx->listingShouldContain(
            'Age 7 should filter out all items, sanity check only',
            [ 'No data available in table' ]
        );
    }


    // TODO: can't change the text of term, can change case.
}