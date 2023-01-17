<?php

namespace App\Entity;

use App\DTO\TermDTO;
use App\Repository\TermRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TermRepository::class)]
#[ORM\Table(name: 'words')]
class Term
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'WoID', type: Types::SMALLINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'Language', inversedBy: 'terms', fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'WoLgID', referencedColumnName: 'LgID', nullable: false)]
    private ?Language $language = null;

    #[ORM\Column(name: 'WoText', length: 250)]
    private ?string $WoText = null;

    #[ORM\Column(name: 'WoTextLC', length: 250)]
    private ?string $WoTextLC = null;

    #[ORM\Column(name: 'WoStatus', type: Types::SMALLINT)]
    private ?int $WoStatus = 1;

    #[ORM\Column(name: 'WoTranslation', length: 500)]
    private ?string $WoTranslation = null;

    #[ORM\Column(name: 'WoRomanization', length: 100)]
    private ?string $WoRomanization = null;

    #[ORM\Column(name: 'WoSentence', length: 1000)]
    private ?string $WoSentence = null;

    #[ORM\Column(name: 'WoWordCount', type: Types::SMALLINT)]
    private ?int $WoWordCount = null;

    #[ORM\JoinTable(name: 'wordtags')]
    #[ORM\JoinColumn(name: 'WtWoID', referencedColumnName: 'WoID')]
    #[ORM\InverseJoinColumn(name: 'WtTgID', referencedColumnName: 'TgID')]
    #[ORM\ManyToMany(targetEntity: TermTag::class, cascade: ['persist'])]
    private Collection $termTags;

    #[ORM\JoinTable(name: 'wordparents')]
    #[ORM\JoinColumn(name: 'WpWoID', referencedColumnName: 'WoID')]
    #[ORM\InverseJoinColumn(name: 'WpParentWoID', referencedColumnName: 'WoID')]
    #[ORM\ManyToMany(targetEntity: Term::class, cascade: ['persist'])]
    private Collection $parents;
    /* Really, a word can have only one parent, but since we have a
       join table, I'll treat it like a many-to-many join in the
       private members, but the interface will only have setParent()
       and getParent(). */

    #[ORM\OneToMany(targetEntity: 'TermImage', mappedBy: 'term', fetch: 'EAGER', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'WiWoID', referencedColumnName: 'WoID', nullable: false)]
    private Collection $images;
    /* Currently, a word can only have one image. */


    public function __construct(?Language $lang = null, ?string $text = null)
    {
        $this->termTags = new ArrayCollection();
        $this->parents = new ArrayCollection();
        $this->images = new ArrayCollection();

        if ($lang != null)
            $this->setLanguage($lang);
        if ($text != null)
            $this->setText($text);
    }

    public function getID(): ?int
    {
        return $this->id;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language): self
    {
        $this->language = $language;
        $this->calcWordCount();
        return $this;
    }

    public function setText(string $WoText): self
    {
        $parts = mb_split("\s+", $WoText);
        $testlen = function($p) { return mb_strlen($p) > 0; };
        $realparts = array_filter($parts, $testlen);
        $cleanword = implode(' ', $realparts);

        $text_changed = $this->WoText != null && $this->WoText != $cleanword;
        if ($this->id != null && $text_changed) {
            $msg = "Cannot change text of term '{$this->WoText}' (id = {$this->id}) once saved.";
            throw new \Exception($msg);
        }

        $this->WoText = $cleanword;
        $this->WoTextLC = mb_strtolower($cleanword);

        $this->calcWordCount();
        return $this;
    }

    private function calcWordCount() {
        $wc = 0;
        if ($this->WoText != null && $this->language != null) {
            $termchars = $this->getLanguage()->getLgRegexpWordCharacters();
            $re = '/([' . $termchars . ']+)/u';
            preg_match_all($re, $this->WoText, $matches);
            if (count($matches) > 0)
                $wc = count($matches[0]);
        }
        $this->setWordCount($wc);
    }

    public function getText(): ?string
    {
        return $this->WoText;
    }

    public function getTextLC(): ?string
    {
        return $this->WoTextLC;
    }

    public function setStatus(?int $n): self
    {
        $this->WoStatus = $n;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->WoStatus;
    }

    public function setWordCount(?int $n): self
    {
        $this->WoWordCount = $n;
        return $this;
    }

    public function getWordCount(): ?int
    {
        return $this->WoWordCount;
    }
    
    public function setTranslation(?string $WoTranslation): self
    {
        $this->WoTranslation = $WoTranslation;
        return $this;
    }

    public function getTranslation(): ?string
    {
        return $this->WoTranslation;
    }

    public function setRomanization(?string $WoRomanization): self
    {
        $this->WoRomanization = $WoRomanization;
        return $this;
    }

    public function getRomanization(): ?string
    {
        return $this->WoRomanization;
    }

    public function setSentence(?string $s): self
    {
        $this->WoSentence = $s;
        return $this;
    }

    public function getSentence(): ?string
    {
        return $this->WoSentence;
    }


    /**
     * @return Collection<int, TextTag>
     */
    public function getTermTags(): Collection
    {
        return $this->termTags;
    }

    public function removeAllTermTags(): void {
        foreach ($this->termTags as $tt) {
            $this->removeTermTag($tt);
        }
    }

    public function addTermTag(TermTag $termTag): self
    {
        if (!$this->termTags->contains($termTag)) {
            $this->termTags->add($termTag);
        }
        return $this;
    }

    public function removeTermTag(TermTag $termTag): self
    {
        $this->termTags->removeElement($termTag);
        return $this;
    }

    /**
     * @return Term or null
     */
    public function getParent(): ?Term
    {
        if ($this->parents->isEmpty())
            return null;
        return $this->parents[0];
    }

    public function setParent(?Term $parent): self
    {
        $this->parents = new ArrayCollection();
        if ($parent != null) {
            /**
             * @psalm-suppress InvalidArgument
             */
            $this->parents->add($parent);
        }
        return $this;
    }

    public function getCurrentImage(): ?string
    {
        if (count($this->images) == 0) {
            return null;
        }
        $i = $this->images->getValues()[0];
        return $i->getSource();
    }

    public function setCurrentImage(?string $s): self
    {
        if (! $this->images->isEmpty()) {
            $this->images->remove(0);
        }
        if ($s != null) {
            $ti = new TermImage();
            $ti->setTerm($this);
            $ti->setSource($s);
            /**
             * @psalm-suppress InvalidArgument
             */
            $this->images->add($ti);
        }
        return $this;
    }

    public function createTermDTO(): TermDTO
    {
        $f = new TermDTO();
        $f->id = $this->getID();
        $f->language = $this->getLanguage();
        $f->Text = $this->getText();
        $f->Status = $this->getStatus();
        $f->Translation = $this->getTranslation();
        $f->Romanization = $this->getRomanization();
        $f->Sentence = $this->getSentence();
        $f->WordCount = $this->getWordCount();
        $f->CurrentImage = $this->getCurrentImage();

        $p = $this->getParent();
        if ($p != null)
            $f->ParentText = $p->getText();

        foreach ($this->getTermTags() as $tt) {
            $f->termTags[] = $tt->getText();
        }

        return $f;
    }

}
