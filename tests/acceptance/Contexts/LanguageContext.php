<?php declare(strict_types=1);

namespace App\Tests\acceptance\Contexts;

class LanguageContext
{

    private $client;

    public function __construct($client) {
        $this->client = $client;
    }

    public function updateLanguageForm($updates) {
        $crawler = $this->client->refreshCrawler();
        $form = $crawler->selectButton('Save')->form();
        foreach (array_keys($updates) as $f) {
            $form["language[{$f}]"] = $updates[$f];
        }
        $crawler = $this->client->submit($form);
        usleep(300 * 1000);
    }

}