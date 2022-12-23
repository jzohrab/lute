<?php

namespace App\Entity;

class TextItem
{

    public int $Order;
    public string $Text;
    public int $WordCount;

    public string $TextLC;
    public int $SeID;
    public int $IsWord;
    public int $TextLength;

    public ?int $WoID;
    public ?string $WoText;
    public ?int $WoStatus;
    public ?string $WoTranslation;
    public ?string $WoRomanization;
    public ?string $Tags;

    public ?int $ParentWoID;
    public ?string $ParentWoTextLC;
    public ?string $ParentWoTranslation;
    public ?string $ParentTags;


    private function strToHex($string): string
    {
        $hex='';
        for ($i=0; $i < strlen($string); $i++) {
            $h = dechex(ord($string[$i]));
            $h = str_pad($h, 2, '0', STR_PAD_LEFT);
            $hex .= $h; 
        }
        return strtoupper($hex);
    }

    /**
     * Returns "TERM" + non-hexified string.
     * Escapes everything to "HEXxx" but not 0-9, a-z, A-Z, and unicode >= (hex 00A5, dec 165)
     */
    public function getTermClassname(): string
    {
        $string = $this->TextLC;
        $length = mb_strlen($string, 'UTF-8');
        $r = '';
        for ($i=0; $i < $length; $i++)
        {
            $c = mb_substr($string, $i, 1, 'UTF-8');
            $add = $c;

            $o = ord($c);
            if (
                ($o < 48) ||
                ($o > 57 && $o < 65) ||
                ($o > 90 && $o < 97) ||
                ($o > 122 && $o < 165)
            ) {
                $add = 'HEX' . $this->strToHex($c);
            }

            $r .= $add; 
        }

        return "TERM{$r}";
    }

    public function getSpanID(): string {
        $parts = [
            'ID',
            $this->Order,
            max(1, $this->WordCount)
        ];
        return implode('-', $parts);
    }

    public function getHtmlClassString(): string {
        $tc = $this->getTermClassname();
        if ($this->WoID == 0) {
            return "click word wsty status0 {$tc}";
        }
        return "click word wsty word{$this->WoID} status{$this->WoStatus} {$tc}";
    }
}