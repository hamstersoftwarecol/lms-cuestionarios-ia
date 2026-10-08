<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'model', 'success', 'prompt_tokens', 'output_tokens', 'duration_ms', 'error'])]
class AiRequest extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    public const TYPES = [
        'extract' => 'Escaneo de documentos',
        'quiz' => 'Generación de cuestionarios',
        'summary' => 'Resúmenes',
        'tts' => 'Texto a voz',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Registros eliminados por `php artisan model:prune` (programado a diario). */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(365));
    }
}
