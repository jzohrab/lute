<?php

namespace tests\App\Entity;
 
use App\Entity\Term;
use PHPUnit\Framework\TestCase;
 
class Term_Test extends TestCase
{

    public function test_cruft_stripped_on_setWord()
    {
        $cases = [
            [ 'hola', 'hola', 'hola' ],
            [ '    hola    ', 'hola', 'hola' ],
            [ "   hola\tGATO\nok", 'hola GATO ok', 'hola gato ok' ],
        ];
        
        foreach ($cases as $c) {
            $t = new Term();
            $t->setText($c[0]);
            $this->assertEquals($t->getText(), $c[1]);
            $this->assertEquals($t->getTextLC(), $c[2]);
        }
    }

    public function test_getWordCount()
    {
        $cases = [
            [ 'hola', 1 ],
            [ '    hola    ', 1 ],
            [ '  hola 	gato', 2 ],
            [ "HOLA\nhay\tgato  ", 3 ]
        ];
        
        foreach ($cases as $c) {
            $t = new Term();
            $t->setText($c[0]);
            $this->assertEquals($t->getWordCount(), $c[1]);

            // If wc is set, it's used.
            $t->setWordCount(17);
            $this->assertEquals($t->getWordCount(), 17, 'override');
        }
    }

}