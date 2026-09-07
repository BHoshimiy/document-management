<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * Lightweight JSON translation support for {"en": "...", "ru": "..."} columns.
 *
 * Declare on the model:
 *   protected array $translatable = ['name'];
 *
 * Reading  $model->name          -> string in the current app locale (falls back)
 * Reading  $model->getTranslations('name') -> full array
 * Writing  $model->name = ['en' => 'Records', 'ru' => 'Записи']
 * Writing  $model->setTranslation('name', 'ru', 'Записи')
 */
trait HasTranslations
{
    public function getAttributeValue($key): mixed
    {
        if (! $this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        }

        return $this->translate($key, app()->getLocale());
    }

    public function setAttribute($key, $value): mixed
    {
        if ($this->isTranslatableAttribute($key) && is_array($value)) {
            return parent::setAttribute($key, json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        if ($this->isTranslatableAttribute($key) && is_string($value)) {
            return $this->setTranslation($key, app()->getLocale(), $value);
        }

        return parent::setAttribute($key, $value);
    }

    public function translate(string $key, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $translations = $this->getTranslations($key);

        return $translations[$locale]
            ?? $translations[config('app.fallback_locale')]
            ?? (reset($translations) ?: null);
    }

    /** @return array<string, string> */
    public function getTranslations(string $key): array
    {
        $raw = $this->attributes[$key] ?? null;

        if (is_array($raw)) {
            return $raw;
        }

        return json_decode((string) $raw, true) ?: [];
    }

    public function setTranslation(string $key, string $locale, ?string $value): static
    {
        $translations = $this->getTranslations($key);
        $translations[$locale] = $value;

        $this->attributes[$key] = json_encode($translations, JSON_UNESCAPED_UNICODE);

        return $this;
    }

    public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->translatable ?? [], true);
    }

    /** Order/filter by the translated value of a JSON column, e.g. scopeOrderByTranslation. */
    public function scopeOrderByTranslation($query, string $column, string $direction = 'asc')
    {
        $locale = app()->getLocale();

        return $query->orderByRaw(
            sprintf('JSON_UNQUOTE(JSON_EXTRACT(%s, "$.%s")) %s', $column, $locale, $direction === 'desc' ? 'desc' : 'asc')
        );
    }

    public function scopeWhereTranslationLike($query, string $column, string $term)
    {
        return $query->where(function ($q) use ($column, $term) {
            foreach (config('app.supported_locales', ['en', 'ru']) as $locale) {
                $q->orWhere("{$column}->{$locale}", 'like', "%{$term}%");
            }
        });
    }
}
