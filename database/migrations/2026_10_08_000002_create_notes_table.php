<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->text('summary')->nullable();
            $table->string('source_type', 20)->default('text');
            $table->string('original_filename')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('extraction_status', 20)->default('completed');
            $table->text('extraction_error')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('note_tag', function (Blueprint $table) {
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['note_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_tag');
        Schema::dropIfExists('notes');
    }
};
