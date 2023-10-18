<?php

namespace App\Entity;

use DateTime;
use App\Repository\TextRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TextRepository::class)]
#[ORM\Table(name: 'texts')]
class Text
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'TxID', type: Types::SMALLINT)]
    private ?int $TxID = null;

    #[ORM\Column(name: 'TxText', type: Types::TEXT)]
    private string $TxText = '';

    #[ORM\Column(name: 'TxOrder', type: Types::SMALLINT)]
    private int $TxOrder = 1;

    #[ORM\Column(name: 'TxReadDate', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTime $TxReadDate = null;

    #[ORM\ManyToOne(inversedBy: 'Texts')]
    #[ORM\JoinColumn(name: 'TxBkID', referencedColumnName: 'BkID', nullable: false)]
    private ?Book $book = null;

    #[ORM\OneToMany(mappedBy: 'text', targetEntity: Sentence::class, orphanRemoval: true, cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['SeOrder' => 'ASC'])]
    #[ORM\JoinColumn(name: 'SeTxID', referencedColumnName: 'TxID', nullable: false)]
    private Collection $Sentences;


    public function __construct()
    {
        $this->Sentences = new ArrayCollection();
    }


    public function getID(): ?int
    {
        return $this->TxID;
    }

    public function getTitle(): string
    {
        $b = $this->getBook();
        $s = "({$this->getOrder()}/{$b->getPageCount()})";
        $t = "{$b->getTitle()} {$s}";
        return $t;
    }

    public function getText(): string
    {
        return $this->TxText;
    }

    public function setText(string $TxText): self
    {
        $this->TxText = $TxText;
        $this->loadSentences();
        return $this;
    }

    public function setOrder(int $n): self
    {
        $this->TxOrder = $n;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->TxOrder;
    }

    public function getLanguage(): ?Language
    {
        return $this->getBook()->getLanguage();
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function getReadDate(): ?DateTime
    {
        return $this->TxReadDate;
    }

    public function setReadDate(?DateTime $dt): self
    {
        $this->TxReadDate = $dt;
        $this->loadSentences();
        return $this;
    }

    /** Sentences. */

    private function makeSentenceFromTokens($tokens, $senumber) {
        $ptstrings = array_map(fn($t) => $t->token, $tokens);

        $zws = mb_chr(0x200B); // zero-width space.
        $s = implode($zws, $ptstrings);
        $s = trim($s, ' ');

        // The zws is added at the start and end of each
        // sentence, to standardize the string search when
        // looking for terms.
        $s = $zws . $s . $zws;

        $sentence = new Sentence();
        $sentence->setSeOrder($senumber);
        $sentence->setSeText($s);
        return $sentence;
    }


    private function loadSentences() {
        foreach ($this->getSentences() as $s)
            $this->removeSentence($s);

        if ($this->TxReadDate == null)
            return;

        $lang = $this->getLanguage();
        $parser = $lang->getParser();
        $parsedtokens = $parser->getParsedTokens($this->TxText, $lang);

        $curr_sentence_tokens = [];
        $sentence_number = 1;

        foreach ($parsedtokens as $pt) {
            $curr_sentence_tokens[] = $pt;
            if ($pt->isEndOfSentence) {
                $se = $this->makeSentenceFromTokens($curr_sentence_tokens, $sentence_number);
                $this->addSentence($se);

                // Reset for next sentence.
                $curr_sentence_tokens = [];
                $sentence_number += 1;
            }
        }

        // Add any stragglers.
        if (count($curr_sentence_tokens) > 0) {
            $se = $this->makeSentenceFromTokens($curr_sentence_tokens, $sentence_number);
            $this->addSentence($se);
        }
    }

    /**
     * @return Collection<int, Sentence>
     */
    public function getSentences(): Collection
    {
        return $this->Sentences;
    }

    private function addSentence(Sentence $sentence): self
    {
        if (!$this->Sentences->contains($sentence)) {
            $this->Sentences->add($sentence);
            $sentence->setText($this);
        }
        return $this;
    }

    private function removeSentence(Sentence $sentence): self
    {
        if ($this->Sentences->removeElement($sentence)) {
            $sentence->setText(null);
        }
        return $this;
    }

}
