<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = ['name', 'slug', 'order', 'is_default', 'document_folder_id'];

    /** @var array<int, string> */
    protected array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function documentFolder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }
}
