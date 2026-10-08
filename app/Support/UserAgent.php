<?php

namespace App\Support;

/**
 * Descripción legible de un User-Agent («Chrome en Windows») sin dependencias externas.
 */
class UserAgent
{
    public static function describe(?string $agent): string
    {
        if (blank($agent)) {
            return 'Dispositivo desconocido';
        }

        return self::browser($agent).' en '.self::platform($agent);
    }

    public static function isMobile(?string $agent): bool
    {
        return (bool) preg_match('/Mobile|Android|iPhone|iPad/i', (string) $agent);
    }

    private static function browser(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            str_contains($agent, 'curl') => 'cURL',
            default => 'Navegador',
        };
    }

    private static function platform(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'otro sistema',
        };
    }
}
