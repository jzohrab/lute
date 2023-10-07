<?php

namespace App\Render;

use App\Entity\Term;
use App\Entity\Language;
use App\Entity\TextItem;

class RenderableCandidate {
    public int $id;

    public ?Term $term = null;

    public string $displaytext; // Text to show, if there is any overlap
    public string $text; // Actual text of the term
    public int $pos;
    public int $length;
    public int $isword;
    public array $hides = array();
    public bool $render = true;

    public function getTermID(): ?int {
        if ($this->term == null)
            return null;
        return $this->term->getID();
    }
    
    public function OrderEnd(): int {
        return $this->pos + $this->length - 1;
    }

    public function toString(): string {
        $ren = $this->render ? 'true' : 'false';
        $id = $this->term != null ? $this->term->getID() : '-';
        return "{$id}; {$this->text}; {$this->pos}; {$this->length}; render = {$ren}";
    }

    public function makeTextItem(int $pnum, int $seid, int $textid, Language $lang): TextItem {
        /*
        $time_start = microtime(true);
        $msgs = [];
        $logmsg = function($s) use ($time_start, &$msgs) {
            $time_end = microtime(true);
            $time = round($time_end - $time_start, 4);
            $msgs[] = $time . ' ' . $s;
        };
        */

        // $logmsg('start');
        $t = new TextItem();
        $t->Order = $this->pos;
        $t->TextID = $textid;
        $t->LangID = $lang->getLgID();
        $t->DisplayText = $this->displaytext;
        $t->Text = $this->text;
        $t->TokenCount = $this->length;

        $t->TextLC = $lang->getLowercase($this->text);
        $t->ParaID = $pnum;
        $t->SeID = $seid;
        $t->IsWord = $this->isword;
        $t->TextLength = mb_strlen($this->text);

        // $logmsg('done base');
        $term = $this->term;
        if ($term == null) {
            // dump($msgs);
            return $t;
        }

        $t->WoID = $term->getID();
        $t->WoStatus = $term->getStatus();
        $t->FlashMessage = $term->getFlashMessage();

        // Always show tooltip if some things are set.
        $hasExtra = function($cterm) {
            if ($cterm == null)
                return false;
            $noextra = (
                $cterm->getTranslation() == null &&
                $cterm->getRomanization() == null &&
                $cterm->getCurrentImage() == null
            );
            return !$noextra;
        };

        $showtooltip = $hasExtra($term);
        foreach ($term->getParents() as $p)
            $showtooltip = $showtooltip || $hasExtra($p);
        $t->ShowTooltip = $showtooltip;

        return $t;
    }
}
