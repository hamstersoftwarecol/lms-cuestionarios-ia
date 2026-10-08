<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('exam_date');
            $table->unsignedSmallInteger('daily_minutes')->default(60);
            $table->json('topics')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('note_study_plan', function (Blueprint $table) {
            $table->foreignId('study_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();

            $table->primary(['study_plan_id', 'note_id']);
        });

        Schema::create('study_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('note_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quiz_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 20)->default('study');
            $table->date('due_date')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_tasks');
        Schema::dropIfExists('note_study_plan');
        Schema::dropIfExists('study_plans');
    }
};
