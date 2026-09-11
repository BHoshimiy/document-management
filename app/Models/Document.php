<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'document_folder_id',
        'name',
        'path',
        'drive_file_id',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function documentFolder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class);
    }

    public function getUrlAttribute(): ?string
    {
        // Drive-backed files are proxied through the app so DocumentPolicy still applies.
        if ($this->drive_file_id !== null) {
            return route('documents.download', $this);
        }

        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo((string) $this->name, PATHINFO_EXTENSION));
    }

    /** Restrict a query to documents the given user may see. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canManageCatalog()) {
            return $query;
        }

        return $query->whereHas('company', fn ($q) => $q->where('user_id', $user->id));
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['company_id'] ?? null, fn ($q, $v) => $q->where('company_id', $v))
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['document_folder_id'] ?? null, fn ($q, $v) => $q->where('document_folder_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'ilike', "%{$v}%"));
    }
}
