<?php

namespace App\Entity;

use App\Repository\TermTagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TermTagRepository::class)]
#[ORM\Table(name: 'tags')]
class TermTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'TgID', type: Types::SMALLINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'TgText', length: 20)]
    private ?string $text = '';

    #[ORM\Column(name: 'TgComment', length: 200)]
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

    public function setComment(?string $comment): self
    {
        // TODO:fix_tags_table_schema
        // The current schema has the TgComment NOT NULL default '',
        // which was a legacy copy over from LWT.  Really, this should
        // be a nullable column, and all blanks should be NULL.
        // Minor change, a nice-to-have.
        $this->comment = $comment ?? '';
        return $this;
    }

    public static function makeTermTag(string $text, ?string $comment = null): TermTag
    {
        $tt = new TermTag();
        $tt->setText($text);
        if ($comment != null)
            $tt->setComment($comment);
        return $tt;
    }
}
