<?php

namespace App\Utils;

use App\Entity\Language;
use App\Repository\LanguageRepository;
use App\Entity\Book;
use App\Entity\Text;
use App\Repository\TextRepository;
use App\Repository\BookRepository;
use App\Entity\Term;
use App\Domain\TermService;
use App\Domain\JapaneseParser;
use Symfony\Component\Yaml\Yaml;

class DemoDataLoader {

    private LanguageRepository $lang_repo;
    private BookRepository $book_repo;
    private TermService $term_service;


    public function __construct(
        LanguageRepository $lang_repo,
        BookRepository $book_repo,
        TermService $term_service
    ) {
        $this->lang_repo = $lang_repo;
        $this->book_repo = $book_repo;
        $this->term_service = $term_service;
    }


    /**
     * Demo files are stored in root/demo/*.yaml.
     * Hardcoding the path :-)
     */
    public function loadDemoFile($filename) {
        $demodir = dirname(__DIR__) . '/../demo/';
        $d = Yaml::parseFile($demodir . $filename);

        $lang = new Language();

        $load = function($key, $method) use ($d, $lang) {
            if (! array_key_exists($key, $d))
                return;

            $val = $d[$key];

            // Have to put "\*" for external dicts in yaml,
            // but those should be saved as "*" only.
            // if (str_starts_with($val, '\*'))

            // Bools are special
            if (strtolower($val) == 'true')
                $val = true;
            if (strtolower($val) == 'false')
                $val = false;

            $lang->{$method}($d[$key]);
        };

        # Input fields on the user form, e.g.
        # http://localhost:9999/language/1/edit
        $mappings = [
            'name' => 'setLgName',
            'dict_1' => 'setLgDict1URI',
            'dict_2' => 'setLgDict2URI',
            'sentence_translation' => 'setLgGoogleTranslateURI',
            'show_romanization' => 'setLgShowRomanization',
            'right_to_left' => 'setLgRightToLeft',

            # Parsing
            'parser_type' => 'setLgParserType',
            'character_substitutions' => 'setLgCharacterSubstitutions',
            'split_sentences' => 'setLgRegexpSplitSentences',
            'split_sentence_exceptions' => 'setLgExceptionsSplitSentences',
            'word_chars' => 'setLgRegexpWordCharacters',

            # Ignore these!
            'story' => '',
            'sample_terms' => '',
        ];

        foreach (array_keys($d) as $key) {
            $funcname = $mappings[$key];
            if ($funcname != '') {
                $load($key, $funcname);
            }
        }

        $this->lang_repo->save($lang, true);

        if (array_key_exists('story', $d)) {
            $f = $demodir . $d['story'];
            $this->loadBook($lang, $f);
        }
    }

    private function loadBook(Language $lang, $filename) {
        $fullcontent = file_get_contents($filename);
        $content = preg_replace('/#.*\n/u', '', $fullcontent);
        preg_match('/title:\s*(.*)\n/u', $fullcontent, $matches);
        $title = trim($matches[1]);
        $b = Book::makeBook($title, $lang, $content);
        $this->book_repo->save($b, true);
    }

    public static function loadDemoData(
        LanguageRepository $lang_repo,
        BookRepository $book_repo,
        TermService $term_service
    ) {
        $e = Language::makeEnglish();
        $f = Language::makeFrench();
        $s = Language::makeSpanish();
        $g = Language::makeGerman();
        $gr = Language::makeGreek();
        $ar = Language::makeArabic();
        $tr = Language::makeTurkish();
        $cc = Language::makeClassicalChinese();

        $langs = [ $e, $f, $s, $g, $gr, $cc, $ar, $tr ];
        $files = [
            'tutorial.txt',
            'tutorial_follow_up.txt',
            'es_aladino.txt',
            'fr_goldilocks.txt',
            'de_Stadtmusikanten.txt',
            'gr_demo.txt',
            'cc_demo.txt',
            'ar_demo.txt',
            'tr_demo.txt',
        ];

        if (JapaneseParser::MeCab_installed()) {
            $langs[] = Language::makeJapanese();
            $files[] = 'jp_kitakaze_to_taiyou.txt';
        }

        $langmap = [];
        foreach ($langs as $lang) {
            $lang_repo->save($lang, true);
            $langmap[ $lang->getLgName() ] = $lang;
        }

        $conn = Connection::getFromEnvironment();

        foreach ($files as $f) {
            $fname = $f;
            $basepath = __DIR__ . '/../../demo/';
            $fullcontent = file_get_contents($basepath . $fname);
            $content = preg_replace('/#.*\n/u', '', $fullcontent);

            preg_match('/language:\s*(.*)\n/u', $fullcontent, $matches);
            $lang = $langmap[trim($matches[1])];

            preg_match('/title:\s*(.*)\n/u', $fullcontent, $matches);
            $title = trim($matches[1]);

            $b = Book::makeBook($title, $lang, $content);
            $book_repo->save($b, true);
        }

        $term = new Term();
        $term->setLanguage($e);
        $term->setText("your local environment file");
        $term->setStatus(3);
        $term->setTranslation("This is \".env\", your personal file in the project root folder :-)");
        $term_service->add($term, true);
    }

}

?>