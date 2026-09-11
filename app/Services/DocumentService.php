<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\DocumentStorage;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\Storage\GoogleDriveStorage;
use App\Services\Storage\LocalDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    /**
     * $storage is the configured destination for new uploads; reads and deletes
     * route per document instead, so flipping DOCUMENT_STORAGE never strands
     * files that were stored under the other driver.
     */
    public function __construct(
        private readonly DocumentStorage $storage,
        private readonly LocalDocumentStorage $local,
        private readonly GoogleDriveStorage $drive,
    ) {}

    /**
     * Store the uploaded file and create the Document row atomically.
     * If the DB write fails the orphaned file is removed.
     *
     * @param  array{category_id: int, name: ?string}  $attributes
     */
    public function store(Company $company, DocumentFolder $folder, UploadedFile $file, array $attributes): Document
    {
        $stored = $this->storage->store($company, $folder, $file);

        try {
            return DB::transaction(fn () => Document::create([
                'company_id' => $company->id,
                'category_id' => $attributes['category_id'],
                'document_folder_id' => $folder->id,
                'name' => $attributes['name'] ?? $file->getClientOriginalName(),
                'path' => $stored['path'],
                'drive_file_id' => $stored['drive_file_id'],
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]));
        } catch (\Throwable $e) {
            $this->storage->discard($stored);

            throw $e;
        }
    }

    public function stream(Document $document): StreamedResponse
    {
        return $this->storageFor($document)->stream($document);
    }

    public function exists(Document $document): bool
    {
        return $this->storageFor($document)->exists($document);
    }

    public function delete(Document $document): void
    {
        $storage = $this->storageFor($document);

        $document->delete(); // soft delete keeps the row; file kept for restore

        if ($document->isForceDeleting()) {
            $storage->delete($document);
        }
    }

    /** A row carrying a drive_file_id lives in Drive; everything else is on the local disk. */
    private function storageFor(Document $document): DocumentStorage
    {
        return $document->drive_file_id !== null ? $this->drive : $this->local;
    }
}
