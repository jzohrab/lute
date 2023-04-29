<?php

namespace App\Entity;

use App\Repository\TermFlashMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wordflashmessages')]
class TermFlashMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'WfID', type: Types::SMALLINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'WfMessage', length: 200)]
    private ?string $Message = null;

    #[ORM\OneToOne(inversedBy: 'termFlashMessage', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'WfWoID', referencedColumnName: 'WoID', nullable: false)]
    private ?Term $Term = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->Message;
    }

    public function setMessage(string $Message): self
    {
        $this->Message = $Message;

        return $this;
    }

    public function getTerm(): ?Term
    {
        return $this->Term;
    }

    public function setTerm(Term $Term): self
    {
        $this->Term = $Term;

        return $this;
    }
}
