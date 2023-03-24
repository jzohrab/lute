<?php declare(strict_types=1);

// Repository tests require an entity manager.
// See ref https://symfony.com/doc/current/testing.html#integration-tests
// for some notes about the kernel and entity manager.
// Note that tests must be run with the phpunit.xml.dist config file.

require_once __DIR__ . '/db_helpers.php';

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Language;
use App\Entity\Text;
use App\Entity\Term;
use App\Entity\TextTag;
use App\Entity\TermTag;

use App\Repository\TextRepository;
use App\Repository\LanguageRepository;
use App\Repository\TextTagRepository;
use App\Repository\TermTagRepository;
use App\Repository\TermRepository;
use App\Repository\BookRepository;
use App\Repository\ReadingRepository;
use App\Repository\SettingsRepository;
use App\Domain\Dictionary;
use App\Domain\BookBinder;
use App\Domain\ReadingFacade;

use Doctrine\ORM\EntityManagerInterface;

abstract class DatabaseTestBase extends WebTestCase
{

    public EntityManagerInterface $entity_manager;

    public TextRepository $text_repo;
    public LanguageRepository $language_repo;
    public TextTagRepository $texttag_repo;
    public TermTagRepository $termtag_repo;
    public TermRepository $term_repo;
    public BookRepository $book_repo;
    public ReadingRepository $reading_repo;
    public SettingsRepository $settings_repo;

    public Language $spanish;
    public Language $french;
    public Language $english;
    public Language $japanese;

    public Text $spanish_hola_text;
    
    public function setUp(): void
    {
        // Set up db.
        DbHelpers::ensure_using_test_db();
        DbHelpers::clean_db();

        $kernel = static::createKernel();
        $kernel->boot();
        $this->entity_manager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        $this->text_repo = $this->entity_manager->getRepository(App\Entity\Text::class);
        $this->language_repo = $this->entity_manager->getRepository(App\Entity\Language::class);
        $this->texttag_repo = $this->entity_manager->getRepository(App\Entity\TextTag::class);
        $this->termtag_repo = $this->entity_manager->getRepository(App\Entity\TermTag::class);
        $this->term_repo = $this->entity_manager->getRepository(App\Entity\Term::class);
        $this->book_repo = $this->entity_manager->getRepository(App\Entity\Book::class);

        $this->reading_repo = new ReadingRepository($this->entity_manager, $this->term_repo, $this->language_repo);
        $this->settings_repo = new SettingsRepository($this->entity_manager);

        $this->childSetUp();
    }

    public function childSetUp() {
        // no-op, child tests can override this to set up stuff.
    }

    public function tearDown(): void
    {
        // echo "tearing down ... \n";
        $this->childTearDown();
    }

    public function childTearDown(): void
    {
        // echo "tearing down ... \n";
    }

    public function load_languages(): void
    {
        $spanish = new Language();
        $spanish
            ->setLgName('Spanish')
            ->setLgDict1URI('https://www.bing.com/images/search?q=###&form=HDRSC2&first=1&tsc=ImageHoverTitle')
            ->setLgDict2URI('https://es.thefreedictionary.com/###')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#es/en/###');
        $this->language_repo->save($spanish, true);
        $this->spanish = $spanish;

        $french = new Language();
        $french
            ->setLgName('French')
            ->setLgDict1URI('https://fr.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.wordreference.com/fren/###')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#fr/en/###');
        $this->language_repo->save($french, true);
        $this->french = $french;

        $english = new Language();
        $english
            ->setLgName('English')
            ->setLgDict1URI('https://en.thefreedictionary.com/###')
            ->setLgDict2URI('https://www.wordreference.com/en/###')
            ->setLgGoogleTranslateURI('*https://www.deepl.com/translator#en/fr/###');
        $this->language_repo->save($english, true);
        $this->english = $english;
    }

    public function load_spanish_words(): void
    {
        $zws = mb_chr(0x200B);
        $terms = [ "Un{$zws} {$zws}gato", 'lista', "tiene{$zws} {$zws}una", 'listo' ];
        $this->addTerms($this->spanish, $terms);
    }

    public function addTerms(Language $lang, $term_strings) {
        $dict = new Dictionary($this->term_repo);
        $arr = $term_strings;
        if (is_string($term_strings))
            $arr = [ $term_strings ];
        $ret = [];
        foreach ($arr as $t) {
            $term = new Term($lang, $t);
            $dict->add($term, true);
            $ret[] = $term;
        }
        return $ret;
    }

    public function load_french_data(): void
    {
        $this->addTerms($this->french, ['lista']);
        $frid = $this->french->getLgID();
        $frt = new Text();
        $frt->setTitle("Bonjour.");
        $frt->setText("Bonjour je suis lista.");
        $frt->setLanguage($this->french);
        $this->text_repo->save($frt, true);
    }

    public function make_text(string $title, string $text, Language $lang): Text {
        $b = BookBinder::makeBook($title, $lang, $text);
        $this->book_repo->save($b, true);
        $b->fullParse();  // Most tests require full parsing.
        return $b->getTexts()[0];
    }

    public function save_term($text, $s) {
        $textid = $text->getID();
        $dict = new Dictionary($this->term_repo);
        $facade = new ReadingFacade(
            $this->reading_repo,
            $this->text_repo,
            $this->book_repo,
            $this->settings_repo,
            $dict,
            $this->termtag_repo
        );
        $dto = $facade->loadDTO($text->getLanguage()->getLgID(), $s);
        $facade->saveDTO($dto);
    }

    private function get_renderable_textitems($text) {
        $ret = [];

        $dict = new Dictionary($this->term_repo);
        $facade = new ReadingFacade(
            $this->reading_repo,
            $this->text_repo,
            $this->book_repo,
            $this->settings_repo,
            $dict,
            $this->termtag_repo
        );

        $ss = $facade->getSentences($text);
        foreach ($ss as $s) {
            foreach ($s->renderable() as $ti) {
                $ret[] = $ti;
            }
        }
        return $ret;
    }

    public function get_rendered_string($text, $imploder = '/', $overridestringize = null) {
        $tis = $this->get_renderable_textitems($text);
        $stringize = function($ti) {
            $zws = mb_chr(0x200B);
            $status = "({$ti->WoStatus})";
            if ($status == '(0)' || $status == '()')
                $status = '';
            return str_replace($zws, '', "{$ti->Text}{$status}");
        };
        $usestringize = $overridestringize ?? $stringize;
        $ss = array_map($usestringize, $tis);
        return implode($imploder, $ss);
    }

    public function assert_rendered_text_equals($text, $expected) {
        $s = $this->get_rendered_string($text);
        $this->assertEquals($s, $expected);
    }

}
