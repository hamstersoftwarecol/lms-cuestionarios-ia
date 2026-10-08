<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('invite_code', 12)->unique();
            $table->unsignedSmallInteger('max_members')->default(50);
            $table->timestamps();
        });

        Schema::create('study_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10)->default('member');
            $table->timestamp('joined_at')->useCurrent();

            $table->unique(['study_group_id', 'user_id']);
        });

        Schema::create('quiz_study_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['quiz_id', 'study_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_study_group');
        Schema::dropIfExists('study_group_user');
        Schema::dropIfExists('study_groups');
    }
};
