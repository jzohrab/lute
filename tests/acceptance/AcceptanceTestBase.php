<?php declare(strict_types=1);

// Repository tests require an entity manager.
// See ref https://symfony.com/doc/current/testing.html#integration-tests
// for some notes about the kernel and entity manager.
// Note that tests must be run with the phpunit.xml.dist config file.

// This is a copy of ../DatabaseTestBase.php, pretty much ...
// it extends PantherTestCase to allow for all of the client
// asserts.
//
// There's probably a better way to do this, but this is fine.

namespace App\Tests\acceptance;

require_once __DIR__ . '/../db_helpers.php';


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Panther\PantherTestCase;

use App\Tests\acceptance\Contexts\ReadingContext;
use App\Tests\acceptance\Contexts\LanguageContext;
use App\Tests\acceptance\Contexts\BookContext;
use App\Tests\acceptance\Contexts\TermContext;
use App\Tests\acceptance\Contexts\TermTagContext;
use App\Tests\acceptance\Contexts\TermUploadContext;

abstract class AcceptanceTestBase extends PantherTestCase
{

    public int $spanishid;
    public int $englishid;
    public bool $mecab_installed;

    public $client;

    public function setUp(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        // App auto-started using the built-in web server
        $this->client = static::createPantherClient();

        $this->client->request('GET', '/dangerous/create_test_db');
        $this->client->waitForElementToContain('body', 'TEST DB CREATED');

        // Get the language ID via UI query.
        // Could (should?) have done this by clicking links
        // and inspecting the URL ...
        $getLanguageID = function($name, $client) {
            $sql = 'select LgID from languages where LgName = "' . $name . '"';
            $crawler = $client->request('GET', '/dangerous/sqlresult/' . $sql);
            $nodes = $crawler->filter('p');
            $lgid = $nodes->eq(0)->text();
            // print($name . ': ' . $lgid);
            return intval($lgid);
        };

        $this->spanishid = $getLanguageID('Spanish', $this->client);
        $this->englishid = $getLanguageID('English', $this->client);

        $crawler = $this->client->request('GET', '/dangerous/mecab_installed');
        $nodes = $crawler->filter('p');
        $check_mecab_installed = $nodes->eq(0)->text();
        $this->mecab_installed = ($check_mecab_installed == 'yes');
        
        $this->childSetUp();
    }

    public function tearDown(): void
    {
        $this->childTearDown();
    }

    // no-op child setUp and tearDown, child tests can override
    public function childSetUp() {}

    public function childTearDown(): void {}

    /**
     * Create a text via the UI.
     */
    public function make_text(string $title, string $text, int $langid) {
        $this->client->request('GET', '/');
        $crawler = $this->client->refreshCrawler();
        // Have to filter or the "create new text" link isn't shown.
        $crawler->filter("input")->sendKeys($title);
        usleep(1000 * 1000); // 1 sec
        $this->client->clickLink('Create new Text');

        $ctx = $this->getBookContext();
        $updates = [
            'language' => $langid,
            'Title' => $title,
            'Text' => $text,
        ];
        $ctx->updateBookForm($updates);
        $this->client->waitForElementToContain('body', $title);
        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', $title);
    }


    public function clickLinkID($linkid) {
        $crawler = $this->client->refreshCrawler();
        $link = $crawler->filter($linkid)->link();
        $this->client->click($link);
    }

    public function getReadingContext() {
        return new ReadingContext($this->client);
    }

    public function getBookContext() {
        return new BookContext($this->client);
    }

    public function getLanguageContext() {
        return new LanguageContext($this->client);
    }

    public function getTermContext() {
        return new TermContext($this->client);
    }

    public function getTermTagContext() {
        return new TermTagContext($this->client);
    }

    public function getTermUploadContext() {
        return new TermUploadContext($this->client);
    }

}
