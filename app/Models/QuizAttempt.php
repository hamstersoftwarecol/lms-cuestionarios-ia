<?php

namespace App\Models;

use App\Enums\AssessmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quiz_id', 'assessment_type', 'confidence_before', 'confidence_after', 'reflection', 'score',
    'total_questions', 'percentage', 'points_earned', 'time_spent', 'started_at', 'completed_at',
])]
class QuizAttempt extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'assessment_type' => AssessmentType::class,
            'percentage' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function grade(): string
    {
        return match (true) {
            $this->percentage >= 90 => 'Excelente',
            $this->percentage >= 75 => 'Muy bien',
            $this->percentage >= 60 => 'Aprobado',
            default => 'Sigue practicando',
        };
    }
}
