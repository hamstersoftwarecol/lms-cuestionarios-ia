<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'action', 'description', 'subject_type', 'subject_id', 'properties', 'ip_address', 'user_agent'])]
class ActivityLog extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** Registros eliminados por `php artisan model:prune` (programado a diario). */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(180));
    }
}
