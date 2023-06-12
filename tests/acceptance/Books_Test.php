<?php declare(strict_types=1);

namespace App\Tests\Acceptance;

use App\Entity\Status;

class Books_Test extends AcceptanceTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    private function getBookTableRows() {
        $this->client->switchTo()->defaultContent();
        $crawler = $this->client->refreshCrawler();
        $nodes = $crawler->filter('#booktable tbody tr');
        $tis = [];
        for ($i = 0; $i < count($nodes); $i++) {
            $n = $nodes->eq($i);
            $tis[] = $n;
        }
        return $tis;
    }

    private function getBookTableContent() {
        $rows = $this->getBookTableRows();
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

    private function updateBookForm($updates) {
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Save')->form();
        foreach (array_keys($updates) as $f) {
            $form["book_dto[{$f}]"] = $updates[$f];
        }
        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

    private function listingShouldContain($msg, $expected) {
        $this->assertEquals($expected, $this->getBookTableContent(), $msg);
    }

    ///////////////////////
    // Tests

    public function test_book_table_not_shown_if_no_books(): void
    {
        $this->client->request('GET', '/');
        $crawler = $this->client->refreshCrawler();
        $bt = $crawler->filter('#booktable');
        $this->assertEquals(0, count($bt), 'no nodes');
    }


    /**
     * @group smoketestbook
     */
    public function test_create_book(): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Create new Text');

        $updates = [
            'language' => $this->spanish->getLgID(),
            'Title' => 'Hola',
            'Text' => 'Hola. Tengo un gato.',
        ];
        $this->updateBookForm($updates);

        $this->client->request('GET', '/');
        $expected = [ 'Hola; Spanish; ; 4 (0%);  ' ];
        $this->assertEquals($expected, $this->getBookTableContent());
    }
    
}