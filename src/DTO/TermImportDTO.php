<?php

namespace App\DTO;

use App\Entity\Book;
use App\Entity\Language;
use App\Repository\TextTagRepository;

class TermImportDTO
{

    /** uploaded file. */
    public ?string $TextFile = null;

}