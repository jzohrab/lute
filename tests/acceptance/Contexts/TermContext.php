<?php declare(strict_types=1);

namespace App\Tests\acceptance\Contexts;

use Facebook\WebDriver\WebDriverKeys;

class TermContext
{

    private $client;

    public function __construct($client) {
        $this->client = $client;
    }

    public function getTermTableRows() {
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

    public function getTermTableContent() {
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

    public function updateTermForm($updates) {
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Save')->form();

        $checkkeys = array_keys($updates);
        $checkkeys = array_filter($checkkeys, fn($s) => $s != 'Tags' && $s != 'Parents');
        foreach ($checkkeys as $f) {
            $form["term_dto[{$f}]"] = $updates[$f];
        }

        $valOrEmpty = function($key, $arr) {
            if (! array_key_exists($key, $arr))
                return [];
            return $arr[$key];
        };

        $tags = $valOrEmpty('Tags', $updates);
        if (count($tags) > 0) {
            $fs = 'ul#termtagslist li.tagit-new > input.ui-autocomplete-input';
            $tt = $crawler->filter($fs);
            \PHPUnit\Framework\Assert::assertEquals(1, count($tt), 'found single tag input');
            $input = $tt->eq(0);
            $input->sendkeys(implode(' ', $tags));
        }

        $parents = $valOrEmpty('Parents', $updates);
        foreach ($parents as $parent) {
            $fs = 'ul#parentslist li.tagit-new > input.ui-autocomplete-input';
            $tt = $crawler->filter($fs);
            \PHPUnit\Framework\Assert::assertEquals(1, count($tt), 'found single parent input');
            $input = $tt->eq(0);
            $input->sendkeys($parent);
            $input->sendkeys(WebDriverKeys::RETURN_KEY);
            usleep(300 * 1000);
        }

        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

    public function assertFilterVisibilityIs($expected_visibility, $msg = '') {
        $crawler = $this->client->refreshCrawler();
        $actual = $crawler->filter("#filtParentsOnly")->isDisplayed();
        \PHPUnit\Framework\Assert::assertEquals($actual, $expected_visibility, 'visibility ' . $msg);
    }

    public function listingShouldContain($msg, $expected) {
        \PHPUnit\Framework\Assert::assertEquals($expected, $this->getTermTableContent(), $msg);
    }
   
}