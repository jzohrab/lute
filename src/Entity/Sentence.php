<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'sentences')]
class Text
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'SeID', type: Types::SMALLINT)]
    private ?int $SeID = null;

    #[ORM\ManyToOne(inversedBy: 'Sentences')]
    #[ORM\JoinColumn(name: 'SeTxID', referencedColumnName: 'TxID', nullable: false)]
    private ?Text $book = null;

    #[ORM\Column(name: 'SeOrder', type: Types::SMALLINT)]
    private int $SeOrder = 1;

    #[ORM\Column(name: 'SeText', type: Types::TEXT)]
    private string $SeText = '';


    public function getID(): ?int
    {
        return $this->SeID;
    }

    public function getSeText(): string
    {
        return $this->SeText;
    }

    public function setSeText(string $s): self
    {
        $this->SeText = $s;
        return $this;
    }

    public function setSeOrder(int $n): self
    {
        $this->SeOrder = $n;
        return $this;
    }

    public function getSeOrder(): int
    {
        return $this->SeOrder;
    }

    public function getText(): ?Text
    {
        return $this->text;
    }

    public function setText(?Text $text): self
    {
        $this->text = $text;

        return $this;
    }
}
