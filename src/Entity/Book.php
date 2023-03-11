<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Title = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Language $Language = null;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: Text::class, orphanRemoval: true)]
    private Collection $Texts;

    #[ORM\ManyToMany(targetEntity: TextTag::class)]
    private Collection $Tags;

    #[ORM\Column]
    private ?bool $Archived = null;

    public function __construct()
    {
        $this->Texts = new ArrayCollection();
        $this->Tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->Title;
    }

    public function setTitle(string $Title): self
    {
        $this->Title = $Title;

        return $this;
    }

    public function getLanguage(): ?Language
    {
        return $this->Language;
    }

    public function setLanguage(?Language $Language): self
    {
        $this->Language = $Language;

        return $this;
    }

    /**
     * @return Collection<int, Text>
     */
    public function getTexts(): Collection
    {
        return $this->Texts;
    }

    public function addText(Text $text): self
    {
        if (!$this->Texts->contains($text)) {
            $this->Texts->add($text);
            $text->setBook($this);
        }

        return $this;
    }

    public function removeText(Text $text): self
    {
        if ($this->Texts->removeElement($text)) {
            // set the owning side to null (unless already changed)
            if ($text->getBook() === $this) {
                $text->setBook(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TextTag>
     */
    public function getTags(): Collection
    {
        return $this->Tags;
    }

    public function addTag(TextTag $tag): self
    {
        if (!$this->Tags->contains($tag)) {
            $this->Tags->add($tag);
        }

        return $this;
    }

    public function removeTag(TextTag $tag): self
    {
        $this->Tags->removeElement($tag);

        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->Archived;
    }

    public function setArchived(bool $Archived): self
    {
        $this->Archived = $Archived;

        return $this;
    }

}
