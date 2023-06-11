<?php declare(strict_types=1);

require_once __DIR__ . '/AcceptanceTestBase.php';

use App\Entity\Status;

class Terms_Test extends AcceptanceTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    private function getTermTableRows() {
        $this->client->switchTo()->defaultContent();
        $crawler = $this->client->refreshCrawler();
        $nodes = $crawler->filter('#termtable tbody tr');
        $tis = [];
        for ($i = 0; $i < count($nodes); $i++) {
            $n = $nodes->eq($i);
            $tis[] = $n;
        }
        return $tis;
    }

    private function getTermTableContent() {
        $rows = $this->getTermTableRows();
        $ret = [];
        for ($r = 0; $r < count($rows); $r++) {
            $rowtext = [];
            $tds = $rows[$r]->filter('td');
            for ($i = 0; $i < count($tds); $i++) {
                $rowtext[] = $tds->eq($i)->text();
            }
            $ret[] = implode('; ', $rowtext);
        }
        return $ret;
    }

    private function updateTermForm($updates) {
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Update')->form();
        foreach (array_keys($updates) as $f) {
            $form["term_dto[{$f}]"] = $updates[$f];
        }
        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

    private function assertFilterVisibilityIs($expected_visibility, $msg = '') {
        $crawler = $this->client->refreshCrawler();
        $actual = $crawler->filter("#filtParentsOnly")->isDisplayed();
        $this->assertEquals($actual, $expected_visibility, 'visibility ' . $msg);
    }

    private function listingShouldContain($msg, $expected) {
        $this->assertEquals($expected, $this->getTermTableContent(), $msg);
    }

    ///////////////////////
    // Tests

    public function test_term_table_empty(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $tis = $this->getTermTableRows();

        $expected = [ 'No data available in table' ];
        $this->assertEquals($expected, $this->getTermTableContent());
    }


    public function test_single_term_created(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $this->client->clickLink('Create new');

        $updates = [
            'language' => $this->spanish->getLgID(),
            'Text' => 'gato',
            'Translation' => 'cat'
        ];
        $this->updateTermForm($updates);

        $expected = [ '; gato; ; cat; Spanish; ; New (1)' ];
        $this->assertEquals($expected, $this->getTermTableContent());

        $this->client->clickLink('gato');
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Update')->form();
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
            'language' => $this->spanish->getLgID(),
            'Text' => 'gatos',
            'ParentText' => 'gato',
            'Translation' => 'cat'
        ];
        $this->updateTermForm($updates);

        $expected = [
            '; gato; ; cat; Spanish; ; New (1)',
            '; gatos; gato; cat; Spanish; ; New (1)',
        ];
        $this->assertEquals($expected, $this->getTermTableContent());

        $this->client->clickLink('gatos');
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Update')->form();
        $this->assertEquals($form['term_dto[Text]']->getValue(), 'gatos', 'same term found');
        $this->assertEquals($form['term_dto[ParentText]']->getValue(), 'gato', 'parent set');
    }

    /**
     * @group termlistfilters
     */
    public function test_term_list_filters(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $this->assertFilterVisibilityIs(false, 'Initially not visible');

        $this->client->clickLink('Create new');
        $updates = [
            'language' => $this->spanish->getLgID(),
            'Text' => 'gatos',
            'ParentText' => 'gato',
            'Translation' => 'cat'
        ];
        $this->updateTermForm($updates);

        $this->listingShouldContain('Initial data',
            [
                '; gato; ; cat; Spanish; ; New (1)',
                '; gatos; gato; cat; Spanish; ; New (1)',
            ]
        );

        $this->assertFilterVisibilityIs(false, "Filter not shown");

        $this->client->getMouse()->clickTo("#showHideFilters");
        $this->assertFilterVisibilityIs(true, "Filter shown");

        $this->client->getMouse()->clickTo("#filtParentsOnly");
        usleep(300 * 1000);
        $this->listingShouldContain(
            'Only parent shown',
            [
                '; gato; ; cat; Spanish; ; New (1)'
            ]
        );

        $this->client->getMouse()->clickTo("#showHideFilters");
        $this->assertFilterVisibilityIs(false, "Filter not shown after hide");
        $this->listingShouldContain(
            'All data shown again',
            [
                '; gato; ; cat; Spanish; ; New (1)',
                '; gatos; gato; cat; Spanish; ; New (1)',
            ]
        );

        $this->client->clickLink('gatos');
        $this->updateTermForm(['Status' => Status::IGNORED]);
        usleep(300 * 1000);
        $this->listingShouldContain(
            'Ignored term not included',
            [
                '; gato; ; cat; Spanish; ; New (1)'
            ]
        );

        $this->client->getMouse()->clickTo("#showHideFilters");
        $this->assertFilterVisibilityIs(true, "Filter now shown");
        $this->client->getMouse()->clickTo("#filtIncludeIgnored");
        usleep(300 * 1000);
        $this->listingShouldContain(
            'Ignored term now included',
            [
                '; gato; ; cat; Spanish; ; New (1)',
                '; gatos; gato; cat; Spanish; ; Ignored'
            ]
        );

        $this->client->clickLink('gatos');
        $this->updateTermForm([ 'Status' => 2 ]);
        usleep(300 * 1000);
        $this->assertFilterVisibilityIs(true, "Filter is still shown, was never hidden");
        $this->listingShouldContain(
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
        $this->listingShouldContain(
            'Min status set for filter',
            [
                '; gatos; gato; cat; Spanish; ; New (2)'
            ]
        );

        $crawler = $this->client->refreshCrawler();
        $crawler->filter("#filtAgeMin")->sendKeys('7');
        usleep(300 * 1000);
        $this->listingShouldContain(
            'Age 7 should filter out all items, sanity check only',
            [ 'No data available in table' ]
        );
    }
    
}