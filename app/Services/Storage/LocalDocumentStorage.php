<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Contracts\DocumentStorage;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocalDocumentStorage implements DocumentStorage
{
    public function __construct(private readonly string $disk = 'public') {}

    public function store(Company $company, DocumentFolder $folder, UploadedFile $file): array
    {
        return [
            'path' => $file->store(
                sprintf('companies/%d/folders/%d', $company->id, $folder->id),
                $this->disk
            ),
            'drive_file_id' => null,
        ];
    }

    public function stream(Document $document): StreamedResponse
    {
        return Storage::disk($this->disk)->download($document->path, $document->name);
    }

    public function delete(Document $document): void
    {
        Storage::disk($this->disk)->delete($document->path);
    }

    public function exists(Document $document): bool
    {
        return Storage::disk($this->disk)->exists($document->path);
    }

    /** @param  array{path: ?string, drive_file_id: ?string}  $stored */
    public function discard(array $stored): void
    {
        if (! empty($stored['path'])) {
            Storage::disk($this->disk)->delete($stored['path']);
        }
    }
}
