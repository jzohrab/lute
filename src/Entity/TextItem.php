<?php

namespace App\Entity;

class TextItem
{
    public int $TextID;
    public int $LangID;
    
    public int $Order;

    // The original, un-overlapped text.
    public string $Text;

    // The actual text to display on screen.
    // If part of the text has been overlapped by a
    // prior token, this will be different from Text.
    public string $DisplayText;

    public int $TokenCount;

    public string $TextLC;
    public int $ParaID;
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
    public ?string $FlashMessage = null;

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

    public function getHtmlDisplayText(): string {
        $zws = mb_chr(0x200B);
        return str_replace([$zws, ' '], ['', '&nbsp;'], $this->DisplayText);
    }

    public function getSpanID(): string {
        $parts = [
            'ID',
            $this->Order,
            max(1, $this->TokenCount)
        ];
        return implode('-', $parts);
    }

    private function has_extra_info(): bool {
        $noextra = (
            $this->WoTranslation == null &&
            $this->WoRomanization == null &&
            $this->ImageSource == null &&
            $this->Tags == null &&
            $this->FlashMessage == null &&
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

        if ($this->WoID == null) {
            $classes = [ 'textitem', 'click', 'word', 'status0' ];
            return implode(' ', $classes);
        }

        $st = $this->WoStatus;
        $classes = [
            'textitem', 'click', 'word',
            'word' . $this->WoID, 'status' . $st
        ];

        $showtooltip = ($st != Status::WELLKNOWN && $st != Status::IGNORED);
        if ($showtooltip || $this->has_extra_info())
            $classes[] = 'showtooltip';

        if ($this->FlashMessage != null)
            $classes[] = 'hasflash';

        if ($this->DisplayText != $this->Text)
            $classes[] = 'overlapped';

        return implode(' ', $classes);
    }
}