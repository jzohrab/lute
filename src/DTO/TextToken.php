<?php

namespace App\DTO;

/** DTO for text tokens. **/
class TextToken
{
    public int $TokTxID;
    public int $TokSentenceNumber;
    public int $TokParagraphNumber; // Derived during RenderableSentence loading.
    public int $TokOrder;
    public int $TokIsWord;
    public string $TokText;
}
