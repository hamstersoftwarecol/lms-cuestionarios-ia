<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('note_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('difficulty', 10)->default('mixed');
            $table->string('language', 10)->default('es');
            $table->unsignedSmallInteger('time_limit')->nullable()->comment('Minutos');
            $table->boolean('is_public')->default(false);
            $table->boolean('generated_by_ai')->default(true);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('mcq');
            $table->text('question_text');
            $table->json('options');
            $table->json('correct_answers');
            $table->text('explanation')->nullable();
            $table->string('difficulty', 10)->default('medium');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->string('assessment_type', 10)->default('practice');
            $table->unsignedTinyInteger('confidence_before')->nullable();
            $table->unsignedTinyInteger('confidence_after')->nullable();
            $table->text('reflection')->nullable();
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->unsignedInteger('points_earned')->default(0);
            $table->unsignedInteger('time_spent')->default(0)->comment('Segundos');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'completed_at']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('selected_answers');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
    }
};
