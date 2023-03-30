<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Domain\ParsedToken;
use App\Utils\Connection;

class JapaneseParser extends AbstractParser {

    /** Checking MeCab **/

    public static function MeCab_installed(): bool
    {
        $p = JapaneseParser::MeCab_app();
        return ($p != null);
    }


    public static function MeCab_command(string $args): string
    {
        if (! JapaneseParser::MeCab_installed()) {
            $os = strtoupper(substr(PHP_OS, 0, 3));
            throw new \Exception("MeCab not installed or not on your PATH (OS = {$os})");
        }
        $p = JapaneseParser::MeCab_app();
        $mecab_args = escapeshellcmd($args);
        return $p . ' ' . $mecab_args;
    }


    /**
     * Get the full OS-specific mecab command.
     * Returns null if mecab is not installed or on path, or unknown os.
     */
    private static function MeCab_app(): ?string
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        if ($os == 'LIN' || $os == 'DAR') {
            if (shell_exec("command -v mecab"))
                return 'mecab'; 
        }
        if ($os == 'WIN') {
            if (shell_exec('where /R "%ProgramFiles%\\MeCab\\bin" mecab.exe'))
                return '"%ProgramFiles%\\MeCab\\bin\\mecab.exe"';
            if (shell_exec('where /R "%ProgramFiles(x86)%\\MeCab\\bin" mecab.exe'))
                return '"%ProgramFiles(x86)%\\MeCab\\bin\\mecab.exe"';
            if (shell_exec('where mecab.exe'))
                return 'mecab.exe';
        }
        return null;
    }


    public function getParsedTokens(string $text, Language $lang) {
        $text = trim(preg_replace('/[ \t]+/u', ' ', $text));

        $file_name = tempnam(sys_get_temp_dir(), "lute");
        // We use the format "word  num num" for all nodes
        $mecab_args = '-F %m\t%t\t%h\n -U %m\t%t\t%h\n -E EOP\t3\t7\n -o ' . $file_name;
        $mecab = JapaneseParser::MeCab_command($mecab_args);

        // WARNING: \n is converted to PHP_EOL here!
        $handle = popen($mecab, 'w');
        fwrite($handle, $text);
        pclose($handle);

        $handle = fopen($file_name, 'r');
        $mecabed = fread($handle, filesize($file_name));
        fclose($handle);
        unlink($file_name);

        $tokens = [];
        foreach (explode(PHP_EOL, $mecabed) as $line) {
            // Skip blank lines, or the following line's array
            // assignment fails.
            if (trim($line) == "")
                continue;

            $tab = mb_chr(9);
            list($term, $node_type, $third) = explode($tab, $line);

            $isParagraph = ($term == 'EOP' && $third == '7');
            if ($isParagraph)
                $term = "¶\r";

            $count = 0;
            if (str_contains('2678', $node_type))
                $count = 1;

            $isEOS = str_ends_with($term, "\r");
            $s = str_replace("\r", "", $term);
            $tokens[] = new ParsedToken($s, $count > 0, $isEOS);
        }

        return $tokens;
    }

}