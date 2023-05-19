<?php declare(strict_types=1);

require_once __DIR__ . '/AcceptanceTestBase.php';


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


}