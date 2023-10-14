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
use App\Parse\JapaneseParser;
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
     */
    public function loadDemoLanguage($filename) {
        $lang = Language::fromYaml($filename);

        $should_save = true;
        if ($lang->getLgParserType() == 'japanese' &&
            (! JapaneseParser::MeCab_installed()))
            $should_save = false;

        if ($should_save)
            $this->lang_repo->save($lang, true);
    }

    /**
     * Load all stories, if the language exists!
     */
    public function loadDemoStories() {
        $demoglob = dirname(__DIR__) . '/../demo/stories/*.txt';
        foreach (glob($demoglob) as $filename) {
            // dump($filename);
            $fullcontent = file_get_contents($filename);
            $content = preg_replace('/#.*\n/u', '', $fullcontent);

            preg_match('/language:\s*(.*)\n/u', $fullcontent, $matches);
            $langname = trim($matches[1]);
            $lang = $this->lang_repo->findOneByName($langname);
            if ($lang == null) {
                // Language not loaded, skip this demo.
                continue;
            }

            preg_match('/title:\s*(.*)\n/u', $fullcontent, $matches);
            $title = trim($matches[1]);

            $b = Book::makeBook($title, $lang, $content);
            $this->book_repo->save($b, true);

            if ($b->getTitle() == 'Tutorial' and $lang->getLgName() == 'English') {
                $required_tutorial_id = 9;
                if ($b->getId() != $required_tutorial_id) {
                    throw new \Exception("Tutorial ID = {$required_tutorial_id} is hardcoded in a few places ... fix them.");
                    // Fixes:
                    // templates/index.html.twig
                    // tests/acceptance/Reading_Test.php
                }
            }
        }
    }

    public static function loadDemoData(
        LanguageRepository $lang_repo,
        BookRepository $book_repo,
        TermService $term_service
    ) {
        $ddl = new DemoDataLoader(
            $lang_repo,
            $book_repo,
            $term_service
        );

        $langglob = dirname(__DIR__) . '/../demo/languages/*.yaml';
        foreach (glob($langglob) as $filename) {
            $ddl->loadDemoLanguage($filename);
        }
        $ddl->loadDemoStories();
    }

}

?>