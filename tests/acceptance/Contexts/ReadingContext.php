<?php declare(strict_types=1);

namespace App\Tests\acceptance\Contexts;

class ReadingContext
{

    private $client;

    public function __construct($client) {
        $this->client = $client;
    }
    
    public function getTextitems() {
        $this->client->switchTo()->defaultContent();
        $crawler = $this->client->refreshCrawler();
        $nodes = $crawler->filterXPath('//span[contains(@class, "textitem")]');
        $tis = [];
        for ($i = 0; $i < count($nodes); $i++) {
            $n = $nodes->eq($i);
            $tis[] = $n;
        }
        return $tis;
    }

    public function getTextitemsMapByText() {
        $tis = $this->getTextitems();

        // Load all text nodes into a map, keyed by text.
        // Note that if multiple items have the same text, they're included in an array.
        $tempmap = [];
        foreach ($tis as $n) {
            $k = $n->text();
            if (! array_key_exists($k, $tempmap)) {
                // dump('not found key = "' . $k . '", adding');
                $tempmap[$k] = [];
            }
            $tempmap[$k][] = $n;
        }
        $mapbytext = [];
        foreach (array_keys($tempmap) as $k) {
            $v = $tempmap[$k];
            if (count($v) == 1)
                $v = $v[0];
            $mapbytext[$k] = $v;
        }
        return $mapbytext;
    }

    public function getReadingNodesByText($word) {
        return $this->getTextitemsMapByText()[$word];
    }

    public function assertDisplayedTextEquals($expected, $msg = 'displayed text') {
        $titext = array_map(fn($n) => $n->text(), $this->getTextitems());
        $actual = implode('/', $titext);
        \PHPUnit\Framework\Assert::assertEquals($expected, $actual, $msg);
    }

    public function clickReadingWord($word) {
        $n = $this->getReadingNodesByText($word);
        $nid = $n->attr('id');
        $this->client->getMouse()->clickTo("#{$nid}");
        usleep(300 * 1000);
    }

    public function assertWordDataEquals($word, $exStatus, $exAttrs = []) {
        $n = $this->getReadingNodesByText($word);
        foreach (array_keys($exAttrs) as $k) {
            \PHPUnit\Framework\Assert::assertEquals($n->attr($k), $exAttrs[$k], $word . ' ' . $k);
        }
        $class = $n->attr('class');
        $termclasses = explode(' ', $class);
        $statusMsg = $class . ' does not contain ' . $exStatus;
        \PHPUnit\Framework\Assert::assertTrue(in_array($exStatus, $termclasses), $word . ' ' . $statusMsg);
    }

    public function getWordCssID($word) {
        $n = $this->getReadingNodesByText($word);
        if (count($n) != 1)
            throw new \Exception('0 or multiple ' . $word);
        return '#' . $n->attr('id');
    }

    public function updateTermForm($expected_Text, $updates) {
        $crawler = $this->client->refreshCrawler();
        $frames = $crawler->filter("#reading-frames-right iframe");
        $this->client->switchTo()->frame($frames);
        $crawler = $this->client->refreshCrawler();

        $form = $crawler->selectButton('Save')->form();
        $loaded = $form['term_dto[Text]']->getValue();
        $zws = mb_chr(0x200B);
        $actual = str_replace($zws, '', $loaded);
        \PHPUnit\Framework\Assert::assertEquals($actual, $expected_Text, 'text pre-loaded');

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
        if (count($parents) > 0) {
            $fs = 'ul#parentslist li.tagit-new > input.ui-autocomplete-input';
            $tt = $crawler->filter($fs);
            \PHPUnit\Framework\Assert::assertEquals(1, count($tt), 'found single parent input');
            $input = $tt->eq(0);
            $input->sendkeys(implode(',', $parents));
        }

        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

    public function updateTextBody($new_text) {
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Update')->form();
        $form["text[Text]"] = $new_text;
        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

}