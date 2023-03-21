<?php

namespace App\Domain;

use App\Entity\Text;
use App\Entity\Language;
use App\Domain\ParsedToken;
use App\Utils\Connection;

class RomanceLanguageParser extends AbstractParser {

    public function getParsedTokens(string $text, Language $lang) {
        return $this->parse_to_tokens($text, $lang);
    }

    // A (possibly) easier way to do substitutions -- each
    // pair in $replacements is run in order.
    // Possible entries:
    // ( <src string or regex string (starting with '/')>, <target (string or callback)> )
    private function do_replacements($text, $replacements) {
        foreach($replacements as $r) {
            if ($r == 'skip') {
                continue;
            }

            if ($r == 'trim') {
                $text = trim($text);
                continue;
            }

            $src = $r[0];
            $tgt = $r[1];

            // echo "=====================\n";
            // echo "Applying '$src' \n\n";

            if (! is_string($tgt)) {
                $text = preg_replace_callback($src, $tgt, $text);
            }
            else {
                if (substr($src, 0, 1) == '/')
                    $text = preg_replace($src, $tgt, $text);
                else
                    $text = str_replace($src, $tgt, $text);
            }

            // echo "text is ---------------------\n";
            // echo str_replace("\r", "<RET>\n", $text);
            // echo "\n-----------------------------\n";
        }
        return $text;
    }

    private function parse_to_tokens(string $text, Language $lang) {

        $replace = explode("|", $lang->getLgCharacterSubstitutions());
        foreach ($replace as $value) {
            $fromto = explode("=", trim($value));
            if (count($fromto) >= 2) {
                $rfrom = trim($fromto[0]);
                $rto = trim($fromto[1]);
                $text = str_replace($rfrom, $rto, $text);
            }
        }

        $punct = ParserPunctuation::PUNCTUATION . '\]';

        $splitSentence = $lang->getLgRegexpSplitSentences();
        $termchar = $lang->getLgRegexpWordCharacters();

        $resplitsent = "/(\S+)\s*((\.+)|([$splitSentence]))([]$punct]*)(?=(\s*)(\S+|$))/u";
        $splitSentencecallback = function($matches) use ($lang) {
            $splitex = $lang->getLgExceptionsSplitSentences();
            return $this->find_latin_sentence_end($matches, $splitex);
        };

        $splitThenPunct = "[{$splitSentence}][{$punct}]*";

        /**
         * Following is a hairy set of regular expressions and their
         * replacements that are applied in order.  These were copied
         * practically verbatim from LWT's parsing, and so they have
         * been production-tested by users with their texts.  Some of
         * these regex's may be redundant, or the order in some cases
         * might not matter -- hard to say offhand without getting a
         * bunch of test cases to verify behaviour before refactoring
         * the regexes.
         */
        $text = $this->do_replacements($text, [
            [ "\r\n",  "\n" ],
            [ '{',     '['],
            [ '}',     ']'],
            [ "\n",    ' ¶' ],

            $lang->isLgSplitEachChar() ?
            [ '/([^\s])/u', "$1\t" ] : 'skip',

            'trim',
            [ '/\s+/u',                             ' ' ],
            [ $resplitsent,                         $splitSentencecallback ],
            [ "¶",                                  "¶\r" ],
            [ " ¶",                                 "\r¶" ],
            [ '/([^' . $termchar . '])/u',          "\n$1\n" ],
            [ '/\n(' . $splitThenPunct . ')\n\t/u', "\n$1\n" ],
            [ '/([0-9])[\n]([:.,])[\n]([0-9])/u',   "$1$2$3" ],
            [ "\t",                                 "\n" ],
            [ "\n\n",                               "" ],
            [ "/\r(?=[{$punct} ]*\r)/u",            "" ],
            [ '/[\n]+\r/u',                         "\r" ],
            [ '/\r([^\n])/u',                       "\r\n$1" ],
            [ "/\n[.](?![{$punct}]*\r)/u",          ".\n" ],
            [ "/(\n|^)(?=.?[$termchar][^\n]*\n)/u", "\n1\t" ],
            'trim',
            [ "/(\n|^)(?!1\t)/u",                   "\n0\t" ],

            'trim'
        ]);

        $tokens = [];
        foreach(explode("\n", $text) as $line) {
            if (trim($line) != '') {
                [ $wordcount, $s ] = explode("\t", $line);
                $tokens[] = new ParsedToken($s, $wordcount > 0);
            }
        }

        if (count($tokens) == 0)
            return [];

        // Hack.  If the text doesn't end with a period (or perhaps a
        // return or other punct), the final token is never classified
        // as a word.  This is a hack, in future perhaps this entire
        // approach can be revamped to split words based on word
        // separators and term tokens.
        $lasttok = $tokens[count($tokens) - 1];
        if (preg_match("/^[$termchar]+$/u", $lasttok->token) == 1) {
            $lasttok->isWord = true;
            $tokens[count($tokens) - 1] = $lasttok;
        }
        return $tokens;
    }


    // TODO:refactor - this code is tough to follow. :-)
    /**
     * Find end-of-sentence characters in a sentence using latin alphabet.
     * 
     * @param string[] $matches       All the matches from a capturing regex
     * @param string   $noSentenceEnd If different from '', can declare that a string a not the end of a sentence.
     * 
     * @return string $matches[0] with ends of sentences marked with \t and \r.
     */
    private function find_latin_sentence_end($matches, $noSentenceEnd)
    {
        if (!strlen($matches[6]) && strlen($matches[7]) && preg_match('/[a-zA-Z0-9]/', substr($matches[1], -1))) { 
            return preg_replace("/[.]/", ".\t", $matches[0]); 
        }
        if (is_numeric($matches[1])) {
            if (strlen($matches[1]) < 3) { 
                return $matches[0];
            }
        } else if ($matches[3] && (preg_match('/^[B-DF-HJ-NP-TV-XZb-df-hj-np-tv-xz][b-df-hj-np-tv-xzñ]*$/u', $matches[1]) || preg_match('/^[AEIOUY]$/', $matches[1]))
        ) { 
            return $matches[0]; 
        }
        if (preg_match('/[.:]/', $matches[2]) && preg_match('/^[a-z]/', $matches[7])) {
            return $matches[0];
        }
        if ($noSentenceEnd != '' && preg_match("/^($noSentenceEnd)$/", $matches[0])) {
            return $matches[0]; 
        }
        return $matches[0] . "\r";
    }

}