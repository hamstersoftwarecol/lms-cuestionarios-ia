<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Registro de auditoría: quién hizo qué, sobre qué recurso, desde qué IP y navegador.
 */
class ActivityLogger
{
    public static function log(string $action, string $description, ?Model $subject = null, array $properties = [], ?User $user = null): ?ActivityLog
    {
        try {
            $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

            return ActivityLog::create([
                'user_id' => $user?->id ?? Auth::id(),
                'action' => $action,
                'description' => Str::limit($description, 250),
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
            ]);
        } catch (Throwable $e) {
            Log::error('No se pudo registrar la actividad', ['action' => $action, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
