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

class DemoDataLoader {

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
        $cc = Language::makeClassicalChinese();

        $langs = [ $e, $f, $s, $g, $gr, $cc, $ar ];
        $files = [
            'tutorial.txt',
            'tutorial_follow_up.txt',
            'es_aladino.txt',
            'fr_goldilocks.txt',
            'de_Stadtmusikanten.txt',
            'gr_demo.txt',
            'cc_demo.txt',
            'ar_demo.txt',
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