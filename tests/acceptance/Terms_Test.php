<?php declare(strict_types=1);

require_once __DIR__ . '/AcceptanceTestBase.php';


class Terms_Test extends AcceptanceTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    private function getTermEntries() {
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

    /**
     * @group empty
     */
    public function test_term_table_empty(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $tis = $this->getTermEntries();
        $this->assertEquals(1, count($tis), 'empty table row only');
        $this->assertEquals('No data available in table', $tis[0]->text(), 'content');
    }

    /**
     * @group termformgato
     */
    public function test_single_term_created(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Terms');
        $this->client->clickLink('Create new');

        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Update')->form();
        $form['term_dto[language]'] = $this->spanish->getLgID();
        $form['term_dto[Text]'] = 'gato';
        $form['term_dto[Translation]'] = 'cat';
        $crawler = $this->client->submit($form);
        usleep(300 * 1000);

        $tis = $this->getTermEntries();
        $this->assertEquals(1, count($tis), 'gato');
        $tds = $tis[0]->filter('td');
        $rowtext = [];
        for ($i = 0; $i < count($tds); $i++) {
            $n = $tds->eq($i);
            $rowtext[] = $n->text();
        }
        $actual = implode('; ', $rowtext);
        $expected = '; gato; ; cat; Spanish; ; New (1)';
        $this->assertEquals($expected, $actual, 'content');

        $this->client->clickLink('gato');
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Update')->form();
        $this->assertEquals($form['term_dto[Text]']->getValue(), 'gato', 'same term found');
    }


}