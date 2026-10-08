<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Consulta y cierre de sesiones almacenadas con el driver "database".
 */
class SessionRepository
{
    public static function isDatabaseDriver(): bool
    {
        return config('session.driver') === 'database';
    }

    /**
     * @return Collection<int, object>
     */
    public static function forUser(User $user): Collection
    {
        if (! self::isDatabaseDriver()) {
            return collect();
        }

        return self::decorate(
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get()
        );
    }

    /**
     * @return Collection<int, object>
     */
    public static function all(): Collection
    {
        if (! self::isDatabaseDriver()) {
            return collect();
        }

        $sessions = DB::table(config('session.table', 'sessions'))
            ->whereNotNull('user_id')
            ->orderByDesc('last_activity')
            ->limit(1000)
            ->get();

        $users = User::whereIn('id', $sessions->pluck('user_id')->unique())->get()->keyBy('id');

        return self::decorate($sessions)->each(fn ($session) => $session->user = $users[$session->user_id] ?? null);
    }

    public static function destroy(string $id, ?int $userId = null): int
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('id', $id)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->delete();
    }

    /**
     * Cierra todas las sesiones del usuario y rota el token "recordarme".
     */
    public static function destroyAllFor(User $user, ?string $exceptId = null): int
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if (! self::isDatabaseDriver()) {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->delete();
    }

    /**
     * @param  Collection<int, object>  $sessions
     * @return Collection<int, object>
     */
    private static function decorate(Collection $sessions): Collection
    {
        $current = session()->getId();
        $lifetime = (int) config('session.lifetime');

        return $sessions->map(function ($session) use ($current, $lifetime) {
            $session->device = UserAgent::describe($session->user_agent);
            $session->is_mobile = UserAgent::isMobile($session->user_agent);
            $session->is_current = $session->id === $current;
            $session->last_active_at = Carbon::createFromTimestamp($session->last_activity, config('app.timezone'));
            $session->is_expired = $session->last_active_at->lt(now()->subMinutes($lifetime));
            unset($session->payload);

            return $session;
        });
    }
}
