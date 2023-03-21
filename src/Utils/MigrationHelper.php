<?php

namespace App\Utils;

require_once __DIR__ . '/../../db/lib/mysql_migrator.php';

use App\Entity\Language;
use App\Repository\LanguageRepository;
use App\Entity\Text;
use App\Repository\TextRepository;
use App\Repository\BookRepository;
use App\Entity\Term;
use App\Domain\Dictionary;
use App\Domain\BookBinder;
use App\Domain\JapaneseParser;

// Class for namespacing only.
class MigrationHelper {

    private static function getMigrator($showlogging = false) {
        [ $server, $userid, $passwd, $dbname ] = Connection::getParams();

        $dir = __DIR__ . '/../../db/migrations';
        $repdir = __DIR__ . '/../../db/migrations_repeatable';
        $migration = new \MysqlMigrator($dir, $repdir, $server, $dbname, $userid, $passwd, $showlogging);
        return $migration;
    }

    /**
     * Used by public/index.php to initiate and migrate the database.
     * 
     * Feels kind of messy, not sure where this belongs, but it will do
     * until proven poor.  ... only tested manually.
     *
     * Returns [ messages, error string ]
     */
    public static function doSetup(): array {

        $messages = [];
        $error = null;
        $newdbcreated = false;
        try {
            Connection::verifyConnectionParams();

            $dbexists = Connection::databaseExists();

            if ($dbexists && MigrationHelper::isLearningWithTextsDb()) {
                [ $server, $userid, $passwd, $dbname ] = Connection::getParams();
                $args = [
                    'dbname' => $dbname,
                    'username' => $userid
                ];
                $error = MigrationHelper::renderError('will_not_migrate_lwt_automatically.html.twig', $args);
                return [ $messages, $error ];
            }

            if (! $dbexists) {
                Connection::createBlankDatabase();
                MigrationHelper::installBaseline();
                $newdbcreated = true;
                $messages[] = 'New database created.';
            }

            if (MigrationHelper::hasPendingMigrations()) {
                MigrationHelper::runMigrations();
                if (! $newdbcreated) {
                    $messages[] = 'Database updated.';
                }
            }
        }
        catch (\Exception $e) {
            $args = ['errors' => [ $e->getMessage() ]];
            $error = MigrationHelper::renderError('fatal_error.html.twig', $args);
        }

        return [ $messages, $error ];
    }

    private static function renderError($name, $args = []): string {
        // ref https://twig.symfony.com/doc/2.x/api.html
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../templates/errors');
        $twig = new \Twig\Environment($loader);
        $template = $twig->load($name);
        return $template->render($args);
    }

    public static function hasPendingMigrations() {
        $migration = MigrationHelper::getMigrator();
        return count($migration->get_pending()) > 0;
    }

    public static function runMigrations($showlogging = false) {
        [ $server, $userid, $passwd, $dbname ] = Connection::getParams();
        $migration = MigrationHelper::getMigrator($showlogging);
        $migration->exec("ALTER DATABASE `{$dbname}` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $migration->process();
    }

    public static function installBaseline() {
        $files = [
            'baseline_schema.sql',
            'reference_data.sql'
        ];
        foreach ($files as $f) {
            $basepath = __DIR__ . '/../../db/baseline/';
            MigrationHelper::process_file($basepath . $f);
        }
    }

    private static function process_file($file) {
        $conn = Connection::getFromEnvironment();
        $commands = file_get_contents($file);
        $conn->multi_query($commands);
        do {
            $conn->store_result();
        } while ($conn->next_result());
        
        if ($conn->error) {
            throw new \Exception($conn->error);
        }
    }

    public static function isLuteDemo() {
        [ $server, $userid, $passwd, $dbname ] = Connection::getParams();
        return ($dbname == 'lute_demo');
    }

    public static function isEmptyDemo() {
        if (! MigrationHelper::isLuteDemo())
            return false;

        $conn = Connection::getFromEnvironment();
        $check = $conn
               ->query('select count(*) as c from Languages')
               ->fetch_array();
        $c = intval($check['c']);
        return $c == 0;
    }

    public static function isLearningWithTextsDb() {
        [ $server, $userid, $passwd, $dbname ] = Connection::getParams();
        $sql = "select count(*) as c from information_schema.tables
          where table_schema = '{$dbname}'
          and table_name = '_lwtgeneral'";
        $conn = Connection::getFromEnvironment();
        $check = $conn
               ->query($sql)
               ->fetch_array();
        $c = intval($check['c']);
        return $c == 1;
    }


    public static function loadDemoData(
        LanguageRepository $lang_repo,
        BookRepository $book_repo,
        Dictionary $dictionary
    ) {
        $e = Language::makeEnglish();
        $f = Language::makeFrench();
        $s = Language::makeSpanish();
        $g = Language::makeGerman();
        $cc = Language::makeClassicalChinese();

        $langs = [ $e, $f, $s, $g, $cc ];
        $files = [
            'tutorial.txt',
            'tutorial_follow_up.txt',
            'es_aladino.txt',
            'fr_goldilocks.txt',
            'de_Stadtmusikanten.txt',
            'cc_demo.txt',
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

        foreach ($files as $f) {
            $fname = $f;
            $basepath = __DIR__ . '/../../db/demo/';
            $fullcontent = file_get_contents($basepath . $fname);
            $content = preg_replace('/#.*\n/u', '', $fullcontent);

            preg_match('/language:\s*(.*)\n/u', $fullcontent, $matches);
            $lang = $langmap[$matches[1]];

            preg_match('/title:\s*(.*)\n/u', $fullcontent, $matches);
            $title = $matches[1];

            $b = BookBinder::makeBook($title, $lang, $content);
            $book_repo->save($b, true);
        }

        $term = new Term();
        $term->setLanguage($e);
        $zws = mb_chr(0x200B);
        $term->setText("your{$zws} {$zws}local{$zws} {$zws}environment{$zws} {$zws}file");
        $term->setStatus(3);
        $term->setTranslation("This is \".env.local\", your personal file in the project root folder :-)");
        $dictionary->add($term, true);
    }

}

?>