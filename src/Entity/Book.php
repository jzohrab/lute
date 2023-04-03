<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'books')]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'BkID', type: Types::SMALLINT)]
    private ?int $BkID = null;

    #[ORM\Column(name: 'BkTitle', length: 200)]
    private ?string $Title = null;

    #[ORM\ManyToOne(targetEntity: 'Language', inversedBy: 'books', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'BkLgID', referencedColumnName: 'LgID', nullable: false)]
    private ?Language $Language = null;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: Text::class, orphanRemoval: true, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['TxOrder' => 'ASC'])]
    #[ORM\JoinColumn(name: 'TxBkID', referencedColumnName: 'BkID', nullable: false)]
    private Collection $Texts;

    #[ORM\JoinTable(name: 'booktags')]
    #[ORM\JoinColumn(name: 'BtBkID', referencedColumnName: 'BkID')]
    #[ORM\InverseJoinColumn(name: 'BtT2ID', referencedColumnName: 'T2ID')]
    #[ORM\ManyToMany(targetEntity: TextTag::class, cascade: ['persist'])]
    private Collection $Tags;

    #[ORM\Column(name: 'BkSourceURI', length: 1000, nullable: true)]
    private ?string $BkSourceURI = null;

    #[ORM\Column(name: 'BkCurrentTxID', type: Types::SMALLINT)]
    private int $BkCurrentTxID = 0;
    
    #[ORM\Column(name: 'BkArchived')]
    private ?bool $Archived = false;

    public function __construct()
    {
        $this->Texts = new ArrayCollection();
        $this->Tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->BkID;
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

    public function getPageCount(): int
    {
        return count($this->Texts);
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

    public function fullParse() {
        $texts = [];
        foreach ($this->getTexts() as $t)
            $texts[] = $t;
        $this->getLanguage()->parse($texts);
    }
    
    /**
     * @return Collection<int, TextTag>
     */
    public function getTags(): Collection
    {
        return $this->Tags;
    }

    public function removeAllTags(): void {
        foreach ($this->Tags as $tt) {
            $this->removeTag($tt);
        }
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

    public function getSourceURI(): ?string
    {
        return $this->BkSourceURI;
    }

    public function setSourceURI(?string $BkSourceURI): self
    {
        $this->BkSourceURI = $BkSourceURI;
        return $this;
    }

    public function getCurrentTextID(): int
    {
        return $this->BkCurrentTxID;
    }

    public function setCurrentTextID(int $n): self
    {
        $this->BkCurrentTxID = $n;
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
