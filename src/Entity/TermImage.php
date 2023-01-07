<?php

namespace App\Entity;

use App\Repository\TermTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wordimages')]
class TermImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'WiID', type: Types::SMALLINT)]
    private ?int $id = null;

    // One term has many images.
    #[ORM\ManyToOne(targetEntity: 'Term', inversedBy: 'images')]
    #[ORM\JoinColumn(name: 'WiWoID', referencedColumnName: 'WoID', nullable: false)]
    private Term $term;
    
    #[ORM\Column(name: 'WiSource', length: 500)]
    private ?string $source = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTerm(): ?Term
    {
        return $this->term;
    }

    public function setTerm(Term $term): self
    {
        $this->term = $term;
        $this->term_id = $term->getID();
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }
}
