<?php declare(strict_types=1);

namespace App\Tests\acceptance\Contexts;

class TermUploadContext
{

    private $client;

    public function __construct($client) {
        $this->client = $client;
    }

    public function uploadFile($absolute_file_path) {
        $this->client->submitForm('Import', [
            // to upload a file, the value must be the absolute file path
            'term_import_dto[TextFile]' => $absolute_file_path,
        ]);
    }

}