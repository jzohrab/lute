<?php

namespace App\Domain;

/**
 * Given tokens and a max token count, on each iteration, returns the
 * next bunch of tokens that are a) complete sentences, and b) have a
 * total token count <= the max token count.  Returns "false" if there
 * are no tokens left.
 */
class SentenceGroupIterator
{

    private $tokens = null;
    private $maxcount = 500;
    private $currpos = 0;

    public function __construct($tokens, $maxcount = 500) {
        $this->tokens = $tokens;
        $this->maxcount = $maxcount;
        $currpos = 0;
    }

    public function count() {
        $oldcurrpos = $this->currpos;
        $c = 0;
        while ($this->next())
            $c++;
        $this->currpos = $oldcurrpos;
        return $c;
    }

    public function next() {
        if ($this->currpos >= count($this->tokens))
            return false;

        // start, move forward to find > max tokens or EOF, recording last end of sentence
        // if <= max tokens, return that
        // return slice up to last eos
        $currtokcount = 0;
        $lastEOS = -1;
        $i = $this->currpos;

        while (
            ($currtokcount <= $this->maxcount || $lastEOS == -1)
            && $i < count($this->tokens)
        ) {
            $tok = $this->tokens[$i];
            if ($tok->isEndOfSentence == 1)
                $lastEOS = $i;
            if ($tok->isWord == 1)
                $currtokcount++;
            $i++;
        }

        if ($currtokcount <= $this->maxcount || $lastEOS == -1) {
            $ret = array_slice($this->tokens, $this->currpos, $i - $this->currpos + 1);
            $this->currpos = $i + 1;
            return $ret;
        }
        else {
            $ret = array_slice($this->tokens, $this->currpos, $lastEOS - $this->currpos + 1);
            $this->currpos = $lastEOS + 1;
            return $ret;
        }
    }

}