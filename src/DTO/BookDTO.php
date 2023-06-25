<?php

namespace App\DTO;

use App\Entity\Book;
use App\Entity\Language;
use App\Repository\TextTagRepository;

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
    public function createBook(): Book
    {
        if (is_null($this->language)) {
            throw new \Exception('Language not set');
        }
        if (is_null($this->Title)) {
            throw new \Exception('Title not set');
        }
        if (is_null($this->Text)) {
            throw new \Exception('Text not set');
        }

        $b = Book::makeBook($this->Title, $this->language, $this->Text);
        $b->setSourceURI($this->SourceURI);
        $b->removeAllTags();
        foreach ($this->bookTags as $t) {
            $b->addTag($t);
        }

        return $b;
    }

    /**
     * Load existing book with DTO data.
     */
    public function loadBook(Book $b): Book
    {
        if (is_null($this->Title)) {
            throw new \Exception('Title not set');
        }

        $b->setTitle($this->Title);
        $b->setSourceURI($this->SourceURI);
        $b->removeAllTags();
        foreach ($this->bookTags as $t) {
            $b->addTag($t);
        }

        return $b;
    }

}