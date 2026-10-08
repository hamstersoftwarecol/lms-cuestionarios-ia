<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'start_date', 'exam_date', 'daily_minutes', 'topics', 'status'])]
class StudyPlan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'exam_date' => 'date',
            'topics' => 'array',
            'daily_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StudyTask::class)->orderBy('due_date')->orderBy('id');
    }

    public function progress(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $done = $this->completed_tasks_count ?? $this->tasks()->where('is_completed', true)->count();

        return $total > 0 ? (int) round($done / $total * 100) : 0;
    }

    public function daysUntilExam(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->exam_date, false);
    }
}
