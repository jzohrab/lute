<?php

namespace App\Domain;

/**
 * This class solely exists because writing these punctuations chars
 * directly in RomanceLanguageParser.php messed up my editor.  Yes, that's lame.  No,
 * I don't care.
 */
class ParserPunctuation
{
    const PUNCTUATION = "'`\"”)‘’‹›“„«»』」";
}
