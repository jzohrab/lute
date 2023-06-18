<?php declare(strict_types=1);

namespace App\Tests\acceptance\Contexts;

class TermTagContext
{

    private $client;

    public function __construct($client) {
        $this->client = $client;
    }

    public function getTableRows() {
        $this->client->switchTo()->defaultContent();
        $crawler = $this->client->refreshCrawler();
        $nodes = $crawler->filter('#termtagtable tbody tr');
        $tis = [];
        for ($i = 0; $i < count($nodes); $i++) {
            $n = $nodes->eq($i);
            $tis[] = $n;
        }
        return $tis;
    }

    public function getTableContent() {
        $rows = $this->getTableRows();
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

    public function updateForm($updates) {
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Save')->form();
        foreach (array_keys($updates) as $f) {
            $form["term_tag[{$f}]"] = $updates[$f];
        }
        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

    public function listingShouldContain($msg, $expected) {
        usleep(200 * 1000);
        \PHPUnit\Framework\Assert::assertEquals($expected, $this->getTableContent(), $msg);
    }
   
}