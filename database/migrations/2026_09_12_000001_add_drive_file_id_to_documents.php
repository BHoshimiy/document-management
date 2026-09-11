<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Non-null means the file lives in Google Drive; null means the
            // local disk. Storing it per row keeps the DOCUMENT_STORAGE switch
            // safe to flip — existing documents keep resolving to where they
            // actually are.
            $table->string('drive_file_id')->nullable()->index()->after('path');

            // A Drive-stored document has no local path.
            $table->string('path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['drive_file_id']);
            $table->dropColumn('drive_file_id');

            // Fails if any Drive-stored row is still present.
            $table->string('path')->nullable(false)->change();
        });
    }
};
