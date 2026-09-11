<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Derives URL slugs from the English name of a translatable model.
 *
 * A slug is never user input: it is regenerated from `name[en]` on every save
 * and made unique by appending a counter, so a save can never fail on a slug
 * collision the admin has no field to resolve.
 */
final class Slug
{
    /** Leaves room for a `-NN` suffix inside the string(255) slug columns. */
    private const MAX_BASE_LENGTH = 240;

    /**
     * Build a slug from $source that no row in $query already holds.
     *
     * Pass a query scoped to the uniqueness domain, and include trashed rows —
     * none of the slug unique indexes is filtered on `deleted_at`, so a slug
     * held by a soft-deleted row would still be rejected by the database:
     *
     *   Slug::uniqueFor(Menu::withTrashed(), 'Global GAP', $menu?->id)
     *   Slug::uniqueFor(DocumentFolder::withTrashed(), $name)
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query  the uniqueness domain
     * @param  int|null  $ignoreId  the row being updated, so it keeps its own slug
     */
    public static function uniqueFor(Builder $query, string $source, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($source), self::MAX_BASE_LENGTH, '') ?: 'item';
        $slug = $base;

        for ($suffix = 2; self::taken($query, $slug, $ignoreId); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    private static function taken(Builder $query, string $slug, ?int $ignoreId): bool
    {
        return (clone $query)
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q, int $id) => $q->whereKeyNot($id))
            ->exists();
    }
}
