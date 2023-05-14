<?php declare(strict_types=1);

require_once __DIR__ . '/AcceptanceTestBase.php';


class Reading_Test extends AcceptanceTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    private function getTextitems() {
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

    private function getReadingNodesByText($word) {
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
        // dump($mapbytext);
        return $mapbytext[$word];
    }

    private function assertDisplayedTextEquals($expected, $msg = 'displayed text') {
        $titext = array_map(fn($n) => $n->text(), $this->getTextitems());
        $actual = implode('/', $titext);
        $this->assertEquals($expected, $actual, $msg);
    }

    private function clickReadingWord($word) {
        $n = $this->getReadingNodesByText($word);
        $nid = $n->attr('id');
        $this->client->executeScript("document.querySelector('#{$nid}').click()");
    }

    private function assertWordDataEquals($word, $exStatus, $exAttrs = []) {
        $n = $this->getReadingNodesByText($word);
        foreach (array_keys($exAttrs) as $k) {
            $this->assertEquals($n->attr($k), $exAttrs[$k], $k);
        }
        $termclasses = explode(' ', $n->attr('class'));
        $this->assertTrue(in_array($exStatus, $termclasses), 'status');
    }

    private function updateTermForm($expected_Text, $updates) {
        $crawler = $this->client->refreshCrawler();
        $frames = $crawler->filter('#reading-frames-right iframe');
        $this->client->switchTo()->frame($frames);
        $crawler = $this->client->refreshCrawler();

        $form = $crawler->selectButton('Save')->form();
        $loaded = $form['term_dto[Text]']->getValue();
        $zws = mb_chr(0x200B);
        $actual = str_replace($zws, '', $loaded);
        $this->assertEquals($actual, $expected_Text, 'text pre-loaded');

        foreach (array_keys($updates) as $f) {
            $form["term_dto[{$f}]"] = $updates[$f];
        }
        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

    public function test_reading_with_term_updates(): void
    {
        $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $this->client->request('GET', '/');

        $this->assertPageTitleContains('LUTE');
        $this->assertSelectorTextContains('body', 'Learning Using Texts (LUTE)');
        $this->client->clickLink('Hola');
        $this->assertPageTitleContains('Reading "Hola"');
        $this->assertDisplayedTextEquals('Hola/. /Adios/ /amigo/.');
        $this->clickReadingWord('Hola');

        $updates = [ 'Translation' => 'hello', 'ParentText' => 'adios' ];
        $this->updateTermForm('hola', $updates);

        $this->assertDisplayedTextEquals('Hola/. /Adios/ /amigo/.');
        $this->assertWordDataEquals(
            'Hola', 'status1',
            [ 'data_trans' => 'hello', 'parent_text' => 'adios' ]);
        $this->assertWordDataEquals(
            'Adios', 'status1',
            [ 'data_trans' => 'hello', 'parent_text' => null ]);
    }

    public function test_create_multiword_term(): void
    {
        $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $this->client->request('GET', '/');
        usleep(300 * 1000); // 300k microseconds - should really wait instead ...
        $this->client->clickLink('Hola');

        $this->assertDisplayedTextEquals('Hola/. /Adios/ /amigo/.', 'initial text');

        $adios = $this->getReadingNodesByText('Adios');
        $amigo = $this->getReadingNodesByText('amigo');
        $adios_id = $adios->attr('id');
        $amigo_id = $amigo->attr('id');
        $this->client->getMouse()->mouseDownTo("#{$adios_id}");
        $this->client->getMouse()->mouseUpTo("#{$amigo_id}");
        usleep(300 * 1000); // 300k microseconds - should really wait ...

        $updates = [ 'Translation' => 'goodbye friend' ];
        $this->updateTermForm('adios amigo', $updates);

        $this->assertDisplayedTextEquals('Hola/. /Adios amigo/.', 'adios amigo grouped');
        $this->assertWordDataEquals(
            'Adios amigo', 'status1',
            [ 'data_trans' => 'goodbye friend' ]);
    }

}