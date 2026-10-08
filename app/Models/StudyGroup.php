<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'max_members'])]
class StudyGroup extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (StudyGroup $group) {
            $group->invite_code ??= static::generateInviteCode();
        });
    }

    public static function generateInviteCode(): string
    {
        do {
            // Sin caracteres ambiguos (0/O, 1/I) para que el código sea fácil de dictar.
            $code = collect(range(1, 8))
                ->map(fn () => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)])
                ->implode('');
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'joined_at');
    }

    public function quizzes(): BelongsToMany
    {
        return $this->belongsToMany(Quiz::class)->withPivot('shared_by')->withTimestamps();
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function isFull(): bool
    {
        return $this->members()->count() >= $this->max_members;
    }

    public function formattedCode(): string
    {
        return Str::substr($this->invite_code, 0, 4).'-'.Str::substr($this->invite_code, 4);
    }
}
