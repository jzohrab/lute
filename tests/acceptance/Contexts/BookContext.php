<?php declare(strict_types=1);

namespace App\Tests\acceptance\Contexts;

class BookContext
{

    private $client;

    public function __construct($client) {
        $this->client = $client;
    }

    public function getBookTableRows() {
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

    public function getBookTableContent() {
        $rows = $this->getBookTableRows();
        $ret = [];
        for ($r = 0; $r < count($rows); $r++) {
            $rowtext = [];
            $tds = $rows[$r]->filter('td');
            for ($i = 0; $i < count($tds); $i++) {
                $rowtext[] = trim($tds->eq($i)->text());
            }
            $ret[] = implode('; ', $rowtext);
        }
        return $ret;
    }

    public function updateBookForm($updates) {
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Save')->form();
        foreach (array_keys($updates) as $f) {
            $form["book_dto[{$f}]"] = $updates[$f];
        }
        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

    public function listingShouldContain($msg, $expected) {
        $c = $this->getBookTableContent();
        \PHPUnit\Framework\Assert::assertEquals($expected, $c, $msg);
    }

}