<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = ['name', 'slug', 'order', 'is_default', 'menu_id'];

    /** @var array<int, string> */
    protected array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
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

    /**
     * Categories offered inside a standard: the standard's own plus the global defaults.
     *
     * A global category only counts when it is a default; a default scoped to another
     * menu does not, or it would leak into every menu.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForMenu(Builder $query, ?int $menuId): Builder
    {
        return $query->where(
            fn ($q) => $q
                ->where(fn ($global) => $global->whereNull('menu_id')->where('is_default', true))
                ->orWhere('menu_id', $menuId)
        );
    }
}
