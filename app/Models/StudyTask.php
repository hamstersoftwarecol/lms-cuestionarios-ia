<?php

namespace App\Models;

use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'note_id', 'quiz_id', 'title', 'description', 'type', 'due_date', 'duration_minutes', 'is_completed', 'completed_at'])]
class StudyTask extends Model
{
    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'due_date' => 'date',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function isOverdue(): bool
    {
        return ! $this->is_completed && $this->due_date->lt(today());
    }
}
