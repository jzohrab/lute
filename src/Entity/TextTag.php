<?php

namespace App\Entity;

use App\Repository\TextTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TextTagRepository::class)]
#[ORM\Table(name: 'tags2')]
class TextTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'T2ID', type: Types::SMALLINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'T2Text', length: 20)]
    private ?string $text = '';

    #[ORM\Column(name: 'T2Comment', length: 200)]
    private ?string $comment = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

}
