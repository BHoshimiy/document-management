<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Where an uploaded document physically lives. Selected by the
 * DOCUMENT_STORAGE switch for writes; for reads the implementation is chosen
 * per document, from whether the row carries a drive_file_id.
 */
interface DocumentStorage
{
    /**
     * Put the file away and return the columns identifying it.
     *
     * @return array{path: ?string, drive_file_id: ?string}
     */
    public function store(Company $company, DocumentFolder $folder, UploadedFile $file): array;

    public function stream(Document $document): StreamedResponse;

    public function delete(Document $document): void;

    public function exists(Document $document): bool;

    /**
     * Remove a file identified by the attributes store() returned, after a failed DB write.
     *
     * @param  array{path: ?string, drive_file_id: ?string}  $stored
     */
    public function discard(array $stored): void;
}
