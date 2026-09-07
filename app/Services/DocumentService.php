<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(private readonly string $disk = 'public') {}

    /**
     * Store the uploaded file and create the Document row atomically.
     * If the DB write fails the orphaned file is removed.
     */
    public function store(Company $company, UploadedFile $file, array $attributes): Document
    {
        $path = $file->store(
            sprintf('companies/%d/folders/%d', $company->id, $attributes['document_folder_id']),
            $this->disk
        );

        try {
            return DB::transaction(fn () => Document::create([
                'company_id' => $company->id,
                'category_id' => $attributes['category_id'],
                'document_folder_id' => $attributes['document_folder_id'],
                'name' => $attributes['name'] ?? $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]));
        } catch (\Throwable $e) {
            Storage::disk($this->disk)->delete($path);

            throw $e;
        }
    }

    public function replaceFile(Document $document, UploadedFile $file): Document
    {
        $old = $document->path;

        $document->update([
            'path' => $file->store(
                sprintf('companies/%d/folders/%d', $document->company_id, $document->document_folder_id),
                $this->disk
            ),
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        Storage::disk($this->disk)->delete($old);

        return $document->refresh();
    }

    public function delete(Document $document): void
    {
        $path = $document->path;

        $document->delete(); // soft delete keeps the row; file kept for restore

        if ($document->isForceDeleting()) {
            Storage::disk($this->disk)->delete($path);
        }
    }
}
