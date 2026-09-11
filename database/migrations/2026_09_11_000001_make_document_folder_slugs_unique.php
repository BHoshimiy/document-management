<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suffix cross-menu duplicates before a global unique index can land.
        // DB::table sees soft-deleted rows too, which the index does not filter on.
        $duplicates = DB::table('document_folders')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('count(*) > 1')
            ->pluck('slug');

        foreach ($duplicates as $slug) {
            $ids = DB::table('document_folders')->where('slug', $slug)->orderBy('id')->pluck('id');

            foreach ($ids->skip(1)->values() as $position => $id) {
                DB::table('document_folders')->where('id', $id)->update([
                    'slug' => $slug.'-'.($position + 2),
                ]);
            }
        }

        Schema::table('document_folders', function (Blueprint $table) {
            $table->dropUnique(['menu_id', 'slug']);
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('document_folders', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['menu_id', 'slug']);
        });
    }
};
