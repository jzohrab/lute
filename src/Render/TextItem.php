<?php

namespace App\Render;

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

    // The tooltip should be shown for well-known/ignored TextItems
    // that merit a tooltip.  e.g., if there isn't any actual Term
    // entity associated with this TextItem, nothing more is needed.
    // Also, if there is a Term entity but it's mostly empty, a
    // tooltip isn't useful.
    public bool $ShowTooltip = false;

    public ?int $WoID = null;
    public ?int $WoStatus = null;
    public ?string $FlashMessage = null;

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

        $tooltip =
                 ($st != Status::WELLKNOWN && $st != Status::IGNORED) ||
                 $this->ShowTooltip ||
                 $this->FlashMessage != null;
        if ($tooltip)
            $classes[] = 'showtooltip';

        if ($this->FlashMessage != null)
            $classes[] = 'hasflash';

        if ($this->DisplayText != $this->Text)
            $classes[] = 'overlapped';

        return implode(' ', $classes);
    }
}