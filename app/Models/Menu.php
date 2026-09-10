<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = ['name', 'slug', 'order'];

    /** @var array<int, string> */
    protected array $translatable = ['name'];

    protected function casts(): array
    {
        return ['order' => 'integer'];
    }

    public function documentFolders(): HasMany
    {
        return $this->hasMany(DocumentFolder::class)->orderBy('order');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('order');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
