<?php declare(strict_types=1);

namespace App\Tests\acceptance;

require_once __DIR__ . '/../db_helpers.php';

use App\Utils\DemoDataLoader;
use App\Domain\TermService;

class Reading_Test extends AcceptanceTestBase
{

    public function childSetUp(): void
    {
        $this->load_languages();
    }

    /**
     * @group readingtermupdate
     */
    public function test_reading_with_term_updates(): void
    {
        $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $this->client->request('GET', '/');

        $this->assertPageTitleContains('LUTE');
        $this->assertSelectorTextContains('body', 'Learning Using Texts (LUTE)');
        $this->client->clickLink('Hola');
        $this->assertPageTitleContains('Reading "Hola (1/1)"');

        $ctx = $this->getReadingContext();
        $ctx->assertDisplayedTextEquals('Hola/. /Adios/ /amigo/.');
        $ctx->clickReadingWord('Hola');

        $updates = [ 'Translation' => 'hello', 'Parents' => ['adios'], 'Tags' => [ 'some', 'tags'] ];
        $ctx->updateTermForm('Hola', $updates);

        $ctx->assertDisplayedTextEquals('Hola/. /Adios/ /amigo/.');
        $ctx->assertWordDataEquals('Hola', 'status1');
        $ctx->assertWordDataEquals('Adios', 'status1');
    }

    /**
     * @group readingtermmultipleparents
     */
    public function test_reading_with_term_multiple_parents_updates(): void
    {
        $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $this->client->request('GET', '/');

        $this->assertPageTitleContains('LUTE');
        $this->assertSelectorTextContains('body', 'Learning Using Texts (LUTE)');
        $this->client->clickLink('Hola');
        $this->assertPageTitleContains('Reading "Hola (1/1)"');

        $ctx = $this->getReadingContext();
        $ctx->assertDisplayedTextEquals('Hola/. /Adios/ /amigo/.');
        $ctx->clickReadingWord('Hola');

        $updates = [ 'Translation' => 'hello', 'Parents' => [ 'adios', 'amigo' ] ];
        $ctx->updateTermForm('Hola', $updates);

        $ctx->assertDisplayedTextEquals('Hola/. /Adios/ /amigo/.');
        $ctx->assertWordDataEquals('Hola', 'status1');
        $ctx->assertWordDataEquals('Adios', 'status1');
        $ctx->assertWordDataEquals('amigo', 'status1');
    }

    public function test_create_multiword_term(): void
    {
        $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $this->client->request('GET', '/');
        usleep(300 * 1000); // 300k microseconds - should really wait instead ...
        $this->client->clickLink('Hola');

        $ctx = $this->getReadingContext();
        $ctx->assertDisplayedTextEquals('Hola/. /Adios/ /amigo/.', 'initial text');

        $adios = $ctx->getReadingNodesByText('Adios');
        $amigo = $ctx->getReadingNodesByText('amigo');
        $adios_id = $adios->attr('id');
        $amigo_id = $amigo->attr('id');
        $this->client->getMouse()->mouseDownTo("#{$adios_id}");
        $this->client->getMouse()->mouseUpTo("#{$amigo_id}");
        usleep(300 * 1000); // 300k microseconds - should really wait ...

        $updates = [ 'Translation' => 'goodbye friend' ];
        $ctx->updateTermForm('Adios amigo', $updates);

        $ctx->assertDisplayedTextEquals('Hola/. /Adios amigo/.', 'adios amigo grouped');
        $ctx->assertWordDataEquals('Adios amigo', 'status1');
    }

    /**
     * @group abbrev
     */
    public function test_create_term_with_period(): void
    {
        $this->spanish->setLgExceptionsSplitSentences('cap.');
        $this->language_repo->save($this->spanish, true);

        $this->make_text("Hola", "He escrito cap. uno.", $this->spanish);
        $this->client->request('GET', '/');
        usleep(300 * 1000); // 300k microseconds - should really wait instead ...
        $this->client->clickLink('Hola');

        $ctx = $this->getReadingContext();
        $ctx->assertDisplayedTextEquals('He/ /escrito/ /cap./ /uno/.', 'initial text');

        $ctx->clickReadingWord('cap.');

        $updates = [ 'Translation' => 'chapter' ];
        $ctx->updateTermForm('cap.', $updates);

        $ctx->assertDisplayedTextEquals('He/ /escrito/ /cap./ /uno/.', 'updated');
        $ctx->assertWordDataEquals('cap.', 'status1');

        $cap = $ctx->getReadingNodesByText('cap.');
        $uno = $ctx->getReadingNodesByText('uno');
        $cap_id = $cap->attr('id');
        $uno_id = $uno->attr('id');
        $this->client->getMouse()->mouseDownTo("#{$cap_id}");
        $this->client->getMouse()->mouseUpTo("#{$uno_id}");
        usleep(300 * 1000); // 300k microseconds - should really wait ...

        $updates = [ 'Translation' => 'chap 1' ];
        $ctx->updateTermForm('cap. uno', $updates);

        $ctx->assertDisplayedTextEquals('He/ /escrito/ /cap. uno/.', 're-updated');
        $ctx->assertWordDataEquals('cap. uno', 'status1');
    }


    /**
     * @group hotkeys
     */
    public function test_hotkeys(): void
    {
        $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', 'Hola');
        $this->client->clickLink('Hola');
        $this->client->waitForElementToContain('body', 'Adios');

        $ctx = $this->getReadingContext();
        $ctx->assertWordDataEquals('Hola', 'status0');

        // Blah hacky.
        $wait = function() { usleep(500 * 1000); };

        $ctx->clickReadingWord('Hola');
        $wait();
        $this->client->getKeyboard()->sendKeys('1');
        $wid = $ctx->getWordCssID('Hola');
        $this->client->waitForAttributeToContain($wid, 'class', 'status1');

        $ctx->clickReadingWord('Adios');
        $wait();
        $this->client->getKeyboard()->sendKeys('2');
        $wid = $ctx->getWordCssID('Adios');
        $this->client->waitForAttributeToContain($wid, 'class', 'status2');

        /*
        // VERY INTERESTING ... I can't 'sendkeys' using
        // strings (e.g., "w") because I use Dvorak layout,
        // and when I sendKeys('w'), the driver sends the
        // QWERTY-LAYOUT W, which is "," on Dvorak.
        // So, sendKeys('w') ACTUALLY ends up sending a
        // ',' (verified by javascript "console.log(e.which)").
        //
        // Software is fun.
        $this->clickReadingWord('Adios');
        $this->client->getKeyboard()->sendKeys('w');
        $this->assertWordDataEquals('Adios', 'status99');

        $this->clickReadingWord('amigo');
        $this->client->getKeyboard()->sendKeys('I');
        $this->assertWordDataEquals('amigo', 'status98');
        */
    }

    /**
     * @group wellknown
     */
    public function test_well_known(): void
    {
        $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', 'Hola');
        $this->client->clickLink('Hola');
        $this->client->waitForElementToContain('body', 'Adios');

        $ctx = $this->getReadingContext();
        $ctx->assertWordDataEquals('Hola', 'status0');

        // Blah hacky.
        $wait = function() { usleep(500 * 1000); };

        $ctx->clickReadingWord('Hola');
        $wait();
        $this->client->getKeyboard()->sendKeys('1');
        $wid = $ctx->getWordCssID('Hola');
        $this->client->waitForAttributeToContain($wid, 'class', 'status1');

        $this->clickLinkID('#footerMarkRestAsKnown');
        $ctx->assertWordDataEquals('Adios', 'status99');
        $ctx->assertWordDataEquals('amigo', 'status99');
    }

    /**
     * @group updatetext
     */
    public function test_can_update_text(): void
    {
        $this->make_text("Hola", "HOLA tengo un gato.", $this->spanish);
        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', 'Hola');
        $this->client->clickLink('Hola');
        $this->client->waitForElementToContain('body', 'HOLA');

        $ctx = $this->getReadingContext();
        $ctx->assertDisplayedTextEquals('HOLA/ /tengo/ /un/ /gato/.', 'loaded');

        // Blah hacky.
        $wait = function() { usleep(500 * 1000); };

        $this->clickLinkID('#editText');
        $ctx->updateTextBody('ADIOS y ahora no tengo nada.');
        $wait();
        $ctx->assertDisplayedTextEquals('ADIOS/ /y/ /ahora/ /no/ /tengo/ /nada/.', 'updated');
    }

    /**
     * @group othertext
     */
    public function test_terms_created_in_one_text_are_carried_over_to_other_text(): void
    {
        $this->make_text("Hola", "Hola. Adios amigo.", $this->spanish);
        $this->make_text("Otro", "Tengo otro amigo.", $this->spanish);

        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', 'Hola');
        $this->client->clickLink('Hola');
        $this->client->waitForElementToContain('body', 'Adios');
        $this->clickLinkID('#footerMarkRestAsKnown');

        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', 'Otro');
        $this->client->clickLink('Otro');
        $this->client->waitForElementToContain('body', 'amigo');

        $ctx = $this->getReadingContext();
        $ctx->assertDisplayedTextEquals('Tengo/ /otro/ /amigo/.', 'other text');
        $ctx->assertWordDataEquals('Tengo', 'status0');
        $ctx->assertWordDataEquals('otro', 'status0');
        $ctx->assertWordDataEquals('amigo', 'status99');
    }

    private function goToTutorialFirstPage() {
        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', 'Tutorial');
        $this->client->clickLink('Tutorial');
        $this->client->waitForElementToContain('body', 'Welcome');
    }

    /**
     * @group setreaddate
     */
    public function test_set_read_date() {
        \DbHelpers::clean_db();
        $term_svc = new TermService($this->term_repo);
        DemoDataLoader::loadDemoData($this->language_repo, $this->book_repo, $term_svc);

        // Hitting the db directly, because if I check the objects,
        // Doctrine caches objects and the behind-the-scenes change
        // isn't shown.
        $b = $this->book_repo->find(1); // hardcoded ID :-(
        $this->assertEquals('Tutorial', $b->getTitle(), 'sanity check');
        $txtid = $b->getTexts()[0]->getID();
        $sql = "select txorder,
          case when txreaddate is null then 'no' else 'yes' end
          from texts
          where txid = {$txtid}";

        $links_that_set_ReadDate = [
            "#footerMarkRestAsKnown",
            "#footerMarkRestAsKnownNextPage",
            "#footerNextPage"
        ];
        foreach ($links_that_set_ReadDate as $linkid) {
            \DbHelpers::exec_sql("update texts set TxReadDate = null");
            \DbHelpers::exec_sql("update books set BkCurrentTxID = 0"); // Hack!

            $this->goToTutorialFirstPage();
            \DbHelpers::assertTableContains($sql, [ "1; no" ], 'pre ' . $linkid);
            $this->clickLinkID($linkid);
            \DbHelpers::assertTableContains($sql, [ "1; yes" ], 'post ' . $linkid);
        }

        \DbHelpers::exec_sql("update texts set TxReadDate = null");
        \DbHelpers::exec_sql("update books set BkCurrentTxID = 0"); // Hack!
        $this->goToTutorialFirstPage();
        $this->clickLinkID("#navNext");
        $this->clickLinkID("#navNext");
        $this->clickLinkID("#navPrev");
        $this->clickLinkID("#navNext");
        $this->clickLinkID("#navPrev10");
        $sql = "select * from texts where TxReadDate is not null";
        \DbHelpers::assertRecordcountEquals($sql, 0, "not set for navigation");
    }

    /**
     * @group readsetsbookmark
     */
    public function test_reading_sets_index_page_bookmark() {
        \DbHelpers::clean_db();
        $term_svc = new TermService($this->term_repo);
        DemoDataLoader::loadDemoData($this->language_repo, $this->book_repo, $term_svc);

        $this->goToTutorialFirstPage();
        $this->clickLinkID("#navNext");
        $this->clickLinkID("#navNext");

        $this->client->request('GET', '/');
        $wait = function() { usleep(500 * 1000); };
        $wait();
        $ctx = $this->getBookContext();
        $fullcontent = implode('|', $ctx->getBookTableContent());
        $expected = "Tutorial (3/";
        $this->assertStringContainsString($expected, $fullcontent, $expected . ' not found in ' . $fullcontent);
    }
}