<?php

namespace App\Models;

use App\Enums\Role;
use App\Services\Auth\OtpService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'google_id', 'avatar', 'theme', 'tts_voice'])]
#[Hidden(['password', 'remember_token', 'otp_code'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Valores por defecto en memoria (coinciden con los de la migración).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
        'theme' => 'system',
        'tts_voice' => 'Kore',
        'points' => 0,
        'current_streak' => 0,
        'longest_streak' => 0,
        'otp_attempts' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'last_activity_date' => 'date',
            'last_login_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'points' => 'integer',
            'current_streak' => 'integer',
            'longest_streak' => 'integer',
            'otp_attempts' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * En lugar del enlace firmado de Breeze enviamos un código OTP de 6 dígitos por SMTP.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(OtpService::class)->send($this);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)->withPivot('awarded_at');
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class);
    }

    public function studyTasks(): HasMany
    {
        return $this->hasMany(StudyTask::class);
    }

    public function studyGroups(): BelongsToMany
    {
        return $this->belongsToMany(StudyGroup::class)->withPivot('role', 'joined_at');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
