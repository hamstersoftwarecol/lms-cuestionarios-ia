<?php

namespace App\Models;

use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['note_id', 'title', 'description', 'difficulty', 'language', 'time_limit', 'is_public', 'generated_by_ai'])]
class Quiz extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'is_public' => 'boolean',
            'generated_by_ai' => 'boolean',
            'time_limit' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function studyGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class)->withPivot('shared_by')->withTimestamps();
    }

    /**
     * Cuestionarios que un usuario puede ver: propios, públicos o compartidos en sus grupos.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('user_id', $user->id)
            ->orWhere('is_public', true)
            ->orWhereHas('studyGroups.members', fn (Builder $m) => $m->where('users.id', $user->id)));
    }

    public function isAccessibleBy(User $user): bool
    {
        return $user->isAdmin()
            || $this->user_id === $user->id
            || $this->is_public
            || $this->studyGroups()->whereHas('members', fn (Builder $m) => $m->where('users.id', $user->id))->exists();
    }
}
