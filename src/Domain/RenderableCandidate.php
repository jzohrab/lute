<?php

namespace App\Domain;

use App\Entity\Term;
use App\Entity\TextItem;

class RenderableCandidate {
    public ?Term $term = null;

    public string $text;
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

    public function makeTextItem(int $seid, int $textid, int $langid): TextItem {
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
        $t->LangID = $langid;
        $t->Text = $this->text;
        $t->WordCount = $this->length;
        $t->TokenCount = $this->length;

        $t->TextLC = mb_strtolower($this->text);
        $t->SeID = $seid;
        $t->IsWord = $this->isword;
        $t->TextLength = mb_strlen($this->text);

        // $logmsg('done base');
        if ($this->term == null) {
            // dump($msgs);
            return $t;
        }

        $term = $this->term;
        $t->WoID = $term->getID();
        $t->WoText = $term->getText();
        $t->WoStatus = $term->getStatus();
        $t->WoTranslation = $term->getTranslation();
        $t->WoRomanization = $term->getRomanization();

        $t->ImageSource = $term->getCurrentImage();
        // $logmsg('done basic term');

        $t->Tags = null;
        $tags = $term->getTermTags();
        if (count($tags) > 0) {
            $ts = [];
            foreach ($tags as $tag)
                $ts[] = $tag->getText();
            $t->Tags = implode(', ', $ts);
        }
        // $logmsg('done term tags');

        $p = $term->getParent();
        if ($p == null) {
            // $logmsg('no parent');
            // dump($msgs);
            return $t;
        }

        $t->ParentWoID = $p->getID();
        $t->ParentWoTextLC = $p->getTextLC();
        $t->ParentWoTranslation = $p->getTranslation();
        $t->ParentImageSource = $p->getCurrentImage();
        // $logmsg('done basic parent');

        $t->ParentTags = null;
        $tags = $p->getTermTags();
        if (count($tags) > 0) {
            $ts = [];
            foreach ($tags as $tag)
                $ts[] = $tag->getText();
            $t->ParentTags = implode(', ', $ts);
        }
        // $logmsg('done parent tags, done');
        // dump($msgs);
        return $t;
    }
}
