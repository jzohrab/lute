<?php

namespace App\Entity;

class TextItem
{
    public int $TextID;
    public int $LangID;
    
    public int $Order;
    public string $Text;
    public int $WordCount;
    public int $TokenCount;

    public string $TextLC;
    public int $SeID;
    public int $IsWord;
    public int $TextLength;

    public ?int $WoID = null;
    public ?string $WoText = null;
    public ?int $WoStatus = null;
    public ?string $WoTranslation = null;
    public ?string $WoRomanization = null;
    public ?string $ImageSource = null;
    public ?string $Tags = null;

    public ?int $ParentWoID = null;
    public ?string $ParentWoTextLC = null;
    public ?string $ParentWoTranslation = null;
    public ?string $ParentImageSource = null;
    public ?string $ParentTags = null;

    // Values set by Sentence class to determine if this TextItem
    // should actually be rendered.
    // TODO:dont_update_items - this is an implementation detail that shouldn't be visible here!
    public ?int $OrderEnd;
    public ?array $hides;
    public bool $Render = true;

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

    private function has_extra_info(): bool {
        $noextra = (
            $this->WoTranslation == null &&
            $this->WoRomanization == null &&
            $this->ImageSource == null &&
            $this->Tags == null &&
            $this->ParentWoTextLC == null &&
            $this->ParentWoTranslation == null &&
            $this->ParentImageSource == null &&
            $this->ParentTags == null
        );
        return !$noextra;
    }

    public function getHtmlClassString(): string {
        if ($this->IsWord == 0) {
            return "textitem";
        }
        $tc = $this->getTermClassname();
        if ($this->WoID == null) {
            return "textitem click word status0 {$tc}";
        }

        $st = $this->WoStatus;
        $hidetip = ($st == Status::WELLKNOWN || $st == Status::IGNORED);

        $showtooltip = 'showtooltip';
        if ($hidetip && !$this->has_extra_info())
            $showtooltip = '';

        return "textitem click word word{$this->WoID} status{$st} {$showtooltip} {$tc}";
    }
}