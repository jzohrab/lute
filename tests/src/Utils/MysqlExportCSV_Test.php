<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\MysqlExportCSV;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

// Smoke test only.
final class MysqlExportCSV_Test extends DatabaseTestBase
{

    // https://stackoverflow.com/questions/1653771/how-do-i-remove-a-directory-that-is-not-empty
    private function rrmdir(string $directory)
    {
        $rd = Path::canonicalize($directory);
        // dump($directory . ' , ' . $rd);
        if (!is_dir($rd))
            return;

        // Ref https://www.php.net/manual/en/function.glob.php
        // "Include dotfiles excluding . and .. special dirs with .[!.]*"
        // Brutal.
        $pattern = $rd . '/{.[!.],}*';

        $files = glob($pattern, GLOB_BRACE);
        foreach ($files as $f) {
            if (is_dir($f)) {
                $this->rrmdir($f);
            }
            else {
                unlink($f);
            }
        }
        rmdir($rd);
    }

    public function test_smoke_test() {
        if (str_contains($_ENV['DATABASE_URL'], 'sqlite'))
            $this->markTestSkipped('Not doing export for sqlite database ... have to re-architect this.');
        $csv_export = __DIR__ . '/../../../csv_export';
        $this->rrmdir($csv_export);
        MysqlExportCSV::doExport();

        $files = glob($csv_export . "/*.*");
        $files = array_map(fn($f) => basename($f), $files);
        $this->assertEquals(16, count($files), "all files made");
        $expected = [
            'books.csv',
            'bookstats.csv',
            'booktags.csv',
            'checksum.csv',
            'languages.csv',
            'sentences.csv',
            'settings.csv',
            'tags.csv',
            'tags2.csv',
            'texts.csv',
            'texttags.csv',
            'texttokens.csv',
            'wordimages.csv',
            'wordparents.csv',
            'words.csv',
            'wordtags.csv',
        ];
        $this->assertEquals($files, $expected);
    }

}
