<?php

namespace App\Entity;

use App\Repository\BookRepository;
use App\DTO\BookDTO;
use App\Domain\SentenceGroupIterator;
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

    public static function makeBook(string $title, Language $lang, string $text, int $maxWordTokensPerText = 250) {
        $b = new Book();
        $b->setTitle($title);
        $b->setLanguage($lang);
        $b->setFullText($text, $maxWordTokensPerText);
        return $b;
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
     * Sets the book text, replacing existing text.
     */
    public function setFullText(
        string $fulltext,
        int $maxWordTokensPerText = 250
    )
    {
        // Remove existing texts.
        foreach ($this->Texts as $text) {
            $this->removeText($text);
        }

        $lang = $this->Language;
        $p = $lang->getParser();
        $tokens = $p->getParsedTokens($fulltext, $lang);
        $it = new SentenceGroupIterator($tokens, $maxWordTokensPerText);

        $tokstring = function($tokens) {
            $a = array_map(fn($t) => $t->token, $tokens);
            $ret = implode('', $a);
            $ret = str_replace("\r", '', $ret);
            $ret = str_replace("¶", "\n", $ret);
            return trim($ret);
        };

        $count = $it->count();
        $i = 0;
        while ($toks = $it->next()) {
            $i++;
            $t = new Text();
            $t->setLanguage($lang);
            $t->setOrder($i);
            $t->setText($tokstring($toks));
            $this->addText($t);
        }
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

    private function addText(Text $text): self
    {
        if (!$this->Texts->contains($text)) {
            $this->Texts->add($text);
            $text->setBook($this);
        }
        return $this;
    }

    private function removeText(Text $text): self
    {
        if ($this->Texts->removeElement($text)) {
            $text->setBook(null);
        }
        return $this;
    }

    public function fullParse() {
        $texts = [];
        foreach ($this->getTexts() as $t)
            $texts[] = $t;
        $lang = $this->getLanguage();
        // Have to chunk the page parsing,
        // it takes too much memory to parse
        // large texts otherwise.
        $chunks = array_chunk($texts, 10);
        foreach ($chunks as $chunk) {
            $lang->parse($chunk);
        }
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
