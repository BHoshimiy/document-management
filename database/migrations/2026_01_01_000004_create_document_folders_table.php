<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->json('name');
            $table->string('code')->index();  // e.g. RP-FER-01
            $table->string('slug');
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['menu_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_folders');
    }
};
