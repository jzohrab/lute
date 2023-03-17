<?php

namespace App\DTO;

use App\Entity\Book;
use App\Entity\Language;
use App\Repository\TextTagRepository;
use App\Domain\BookBinder;

class BookDTO
{

    public ?int $id = null;

    public ?Language $language = null;

    public ?string $Title = null;

    public ?string $Text = null;

    public ?string $SourceURI = null;

    public $bookTags;

    public function __construct()
    {
        $this->bookTags = array();
    }


    /**
     * Convert the given BookDTO to a new Book.
     */
    public static function buildBook(BookDTO $dto, TextTagRepository $ttr): Book
    {
        if (is_null($dto->language)) {
            throw new \Exception('Language not set');
        }
        if (is_null($dto->Title)) {
            throw new \Exception('Title not set');
        }
        if (is_null($dto->Text)) {
            throw new \Exception('Text not set');
        }

        $b = BookBinder::makeBook($dto->Title, $dto->language, $dto->Text);
        $b->setSourceURI($dto->SourceURI);
        $b->removeAllTags();
        foreach ($dto->bookTags as $t) {
            $b->addTag($t);
        }

        return $b;
    }

}