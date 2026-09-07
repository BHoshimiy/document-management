<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentFolder extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = ['menu_id', 'name', 'code', 'slug', 'order'];

    /** @var array<int, string> */
    protected array $translatable = ['name'];

    protected function casts(): array
    {
        return ['order' => 'integer'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
