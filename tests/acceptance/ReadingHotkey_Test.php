<?php declare(strict_types=1);

namespace App\Tests\acceptance;

use App\Tests\acceptance\Contexts\ReadingContext;
use Facebook\WebDriver\WebDriverKeys;

class ReadingHotkey_Test extends AcceptanceTestBase
{

    /* Reading URL of Book being manipulated. */
    private string $book_url;

    private ReadingContext $ctx;
    
    private function getClassList($map, $word) {
        $n = $map[$word];
        if (count($n) != 1)
            throw new \Exception('0 or multiple ' . $word);
        return $n->attr('class');
    }

    public function childSetUp(): void
    {
        // Note using 'full' and 'ice' here b/c the test setup for
        // english has single-character-then-point as a regex parsing
        // exception!
        $this->make_text("Test", "a b c d e full. g h ice.", $this->englishid);
        $this->client->request('GET', '/');
        $this->client->waitForElementToContain('body', 'Test');
        $this->client->clickLink('Test');
        $this->client->waitForElementToContain('body', 'full');

        $this->book_url = $this->client->getCurrentURL();
        $this->ctx = $this->getReadingContext();
    }

    /**
     * Assert that the class is only added to the given elements.
     * e.g. assert_classes( [ 'wordhover' => 'a', 'blah' => 'c d' ] )
     * means that _only_ a should have wordhover, and _only_ c and d
     * should have blah.
     */
    private function assert_classes($hsh_class_to_el_array, $message = '') {
        $words = explode(' ', 'a b c d e full g h ice');
        $map = $this->ctx->getTextitemsMapByText();
        $class_map = [];
        foreach ($words as $w) {
            $class_map[$w] = explode(' ', $this->getClassList($map, $w));
        }
        // dump($class_map);
        foreach (array_keys($hsh_class_to_el_array) as $klass) {
            $actual = array_filter($words, fn($w) => in_array($klass, $class_map[$w]));
            $actual = implode(' ', $actual);
            $expected = $hsh_class_to_el_array[$klass];
            \PHPUnit\Framework\Assert::assertEquals($expected, $actual, $message);
        }
    }

    /**
     * @group hotkeys
     */
    public function test_hotkeys(): void {  // V3-port: DONE - not all of it, but enough.
        // This test just wipes terms from the database and reloads
        // the book being tested.  It's a bit hacky (I prefer to have
        // clean state for every distinct test case), but it's faster
        // than doing a full childSetUp() for each scenario.  There
        // may be a better way to do this.
        $reset = function() {
            $this->client->request('GET', '/dangerous/delete_all_terms');
            $this->client->waitForElementToContain('body', 'ALL TERMS DELETED');
            $this->client->request('GET', $this->book_url);
            $this->client->waitForElementToContain('body', 'full');

            // TODO:clipboard_acceptance_check - can't figure out how to get this working yet.
            return;

            // Add textarea to check the clipboard contents ...
            // note I haven't gotten clipboard checks to work yet.
            $script = "var textArea = document.createElement(\"textarea\");
  textArea.setAttribute(\"id\", \"txtClipboard\");
  textArea.setAttribute(\"name\", \"nameClipboard\");
  textArea.value = '.';
  document.body.appendChild(textArea);";
            $this->client->executeScript($script);

            // Copy current text content to "reset" the clipboard.
            $this->client->getMouse()->clickTo("#txtClipboard");
            $this->client->getKeyboard()->pressKey(WebDriverKeys::COMMAND);
            $this->client->getKeyboard()->sendKeys('c');
            $this->client->getKeyboard()->releaseKey(WebDriverKeys::COMMAND);
            usleep(300 * 1000);
        };
        $hover = function($word) {
            $wid = $this->ctx->getWordCssID($word);
            $this->client->getMouse()->mouseMoveTo($wid);
            usleep(300 * 1000);
        };
        $click = function($word) {
            $this->ctx->clickReadingWord($word);
            usleep(300 * 1000);
        };
        $shiftclick = function($word) {
            $this->client->getKeyboard()->pressKey(WebDriverKeys::SHIFT);
            $this->ctx->clickReadingWord($word);
            $this->client->getKeyboard()->releaseKey(WebDriverKeys::SHIFT);
            usleep(300 * 1000);
        };
        $hotkey = function($key) {
            $this->client->getKeyboard()->sendKeys($key);
            usleep(500 * 1000);
        };
        $wait = function($millis = 100) {
            usleep($millis * 1000);
        };

        // TODO:clipboard_acceptance_check - can't figure out how to get this working yet.
        $checkClipboard = function($expected) {
            $this->client->getMouse()->clickTo("#txtClipboard");
            $this->client->getKeyboard()->pressKey(WebDriverKeys::COMMAND);
            $this->client->getKeyboard()->sendKeys('v');
            $this->client->getKeyboard()->releaseKey(WebDriverKeys::COMMAND);

            // $crawler = $this->client->refreshCrawler();
            // $this->assertSelectorTextSame('#txtClipboard', $expected);

            // $cp = $crawler->filter("#txtClipboard");
            // dump($cp);
            // $txt = $cp->text();
            // dump($txt);
            // \PHPUnit\Framework\Assert::assertEquals($expected, $txt, 'clipboard');
        };

        // Fail fast-ish if not qwerty layout.  (I use Dvorak layout,
        // if sendKeys sends the key of the qwerty physical keyboard
        // layout ... very.)
        $get_hotkey_value = function($envkey, $default) {
            if (array_key_exists($envkey, $_ENV))
                return $_ENV[$envkey];
            return $default;
        };

        $hotkey_w = $get_hotkey_value('HOTKEY_WELLKNOWN', 'w');
        $hotkey_copyterm = $get_hotkey_value('HOTKEY_COPYTERM', 'c');
        $hotkey_copypara = $get_hotkey_value('HOTKEY_COPYPARA', 'C');

        $reset();
        $hover('a');
        $this->assert_classes([ 'wordhover' => 'a']);
        $hotkey($hotkey_w);
        $this->assert_classes([ 'status99' => 'a' ], 'well-known');

        // Hovered gets the status update.
        $reset();
        $hover('a');
        $this->assert_classes([ 'wordhover' => 'a' ]);
        $hover('b');
        $this->assert_classes([ 'wordhover' => 'b' ]);
        $hotkey('1');
        $this->assert_classes([ 'status1' => 'b' ], 'hovered word gets status update');

        // Clicked gets the status update.
        $reset();
        $click('c');
        $this->assert_classes([ 'kwordmarked' => 'c', 'wordhover' => '' ]);
        $hover('b');
        $this->assert_classes([ 'kwordmarked' => 'c', 'wordhover' => '' ]);
        $hotkey('1');
        $this->assert_classes([ 'status1' => 'c' ], 'clicked word gets status update');

        // Re-clicking clicked reverts it to hovered.
        $reset();
        $click('c');
        $this->assert_classes([ 'kwordmarked' => 'c', 'wordhover' => '' ]);
        $click('c');
        $this->assert_classes([ 'kwordmarked' => '', 'wordhover' => 'c' ], 'clicking already-clicked word reverts it to hover');

        // Hovered gets the copy.
        $reset();
        $hover('b');
        $hotkey($hotkey_copyterm);
        $wait(1000); // Wait for flash to end ... :-(
        $this->assert_classes([ 'wascopied' => 'a b c d e full' ], 'hovered word gets the copy');

        // Hovered para copy
        $reset();
        $hover('b');
        $hotkey($hotkey_copypara);
        $wait(1000); // Wait for flash to end ... :-(
        $this->assert_classes([ 'wascopied' => 'a b c d e full g h ice' ], 'hovered copy');

        // Clicked gets the copy.
        $reset();
        $click('b');
        $hover('ice');
        $hotkey($hotkey_copyterm);
        $wait(1000); // Wait for flash to end ... :-(
        $this->assert_classes([ 'wascopied' => 'a b c d e full' ], 'clicked word gets the copy');

        // Click drag creates parent term
        $reset();
        $this->client->getMouse()->mouseDownTo($this->ctx->getWordCssID('b'));
        $this->client->getMouse()->mouseUpTo($this->ctx->getWordCssID('d'));
        $wait(1000); // Wait for things to settle ...
        $this->assert_classes([ 'newmultiterm' => 'b c d', 'kwordmarked' => '', 'wordhover' => '' ]);
        // ... hovering has no effect ...
        $hover('e');
        $wait(200);
        $this->assert_classes([ 'newmultiterm' => 'b c d', 'kwordmarked' => '', 'wordhover' => '' ]);
        // ... and then clicking elsewhere stops it.
        $click('e');
        $wait(1000);
        $this->assert_classes([ 'newmultiterm' => '', 'kwordmarked' => 'e', 'wordhover' => '' ]);

        // Click drag removes all other cursor stuff
        $reset();
        $click('ice');
        $this->assert_classes([ 'kwordmarked' => 'ice', 'wordhover' => '' ]);
        $this->client->getMouse()->mouseDownTo($this->ctx->getWordCssID('b'));
        $this->client->getMouse()->mouseUpTo($this->ctx->getWordCssID('d'));
        $wait(1000); // Wait for things to settle ...
        $this->assert_classes([ 'newmultiterm' => 'b c d', 'kwordmarked' => '', 'wordhover' => '' ]);

        // Arrow right is same as clicked, sort of ...
        $reset();
        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ARROW_RIGHT);
        $this->assert_classes([ 'kwordmarked' => 'a', 'wordhover' => '' ]);
        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ARROW_RIGHT);
        $this->assert_classes([ 'kwordmarked' => 'b', 'wordhover' => '' ]);

        // Hover then arrow right moves to next thing
        $reset();
        $hover('b');
        $this->client->getKeyboard()->sendKeys(WebDriverKeys::ARROW_RIGHT);
        $this->assert_classes([ 'kwordmarked' => 'c', 'wordhover' => '' ]);

        // Shift-click selects multiple words
        $reset();
        $click('c');
        $shiftclick('d');
        $shiftclick('e');
        $this->assert_classes([ 'kwordmarked' => 'c d e', 'wordhover' => '' ]);

    }

}