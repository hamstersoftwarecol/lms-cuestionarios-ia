<?php

namespace App\Support;

/**
 * Clases de Tailwind completas por color (Tailwind no detecta clases construidas dinámicamente).
 */
class Palette
{
    private const PILL = [
        'indigo' => 'bg-indigo-100 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-400/30',
        'sky' => 'bg-sky-100 text-sky-700 ring-sky-600/20 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-400/30',
        'emerald' => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-400/30',
        'amber' => 'bg-amber-100 text-amber-800 ring-amber-600/20 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-400/30',
        'rose' => 'bg-rose-100 text-rose-700 ring-rose-600/20 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-400/30',
        'violet' => 'bg-violet-100 text-violet-700 ring-violet-600/20 dark:bg-violet-500/15 dark:text-violet-300 dark:ring-violet-400/30',
        'orange' => 'bg-orange-100 text-orange-700 ring-orange-600/20 dark:bg-orange-500/15 dark:text-orange-300 dark:ring-orange-400/30',
        'teal' => 'bg-teal-100 text-teal-700 ring-teal-600/20 dark:bg-teal-500/15 dark:text-teal-300 dark:ring-teal-400/30',
        'pink' => 'bg-pink-100 text-pink-700 ring-pink-600/20 dark:bg-pink-500/15 dark:text-pink-300 dark:ring-pink-400/30',
        'cyan' => 'bg-cyan-100 text-cyan-700 ring-cyan-600/20 dark:bg-cyan-500/15 dark:text-cyan-300 dark:ring-cyan-400/30',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-600/20 dark:bg-slate-500/20 dark:text-slate-300 dark:ring-slate-400/30',
    ];

    private const DOT = [
        'indigo' => 'bg-indigo-500', 'sky' => 'bg-sky-500', 'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-500', 'violet' => 'bg-violet-500', 'orange' => 'bg-orange-500', 'teal' => 'bg-teal-500',
        'pink' => 'bg-pink-500', 'cyan' => 'bg-cyan-500', 'slate' => 'bg-slate-500',
    ];

    private const GRADIENT = [
        'indigo' => 'from-indigo-500 to-violet-500', 'sky' => 'from-sky-400 to-blue-500', 'emerald' => 'from-emerald-400 to-teal-500',
        'amber' => 'from-amber-400 to-orange-500', 'rose' => 'from-rose-400 to-pink-500', 'violet' => 'from-violet-500 to-fuchsia-500',
        'orange' => 'from-orange-400 to-red-500', 'teal' => 'from-teal-400 to-cyan-500', 'pink' => 'from-pink-400 to-rose-500',
        'cyan' => 'from-cyan-400 to-sky-500', 'slate' => 'from-slate-400 to-slate-600',
    ];

    private const ALERT = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200',
    ];

    public static function pill(?string $color): string
    {
        return self::PILL[$color] ?? self::PILL['slate'];
    }

    public static function dot(?string $color): string
    {
        return self::DOT[$color] ?? self::DOT['slate'];
    }

    public static function gradient(?string $color): string
    {
        return self::GRADIENT[$color] ?? self::GRADIENT['indigo'];
    }

    public static function alert(?string $type): string
    {
        return self::ALERT[$type] ?? self::ALERT['info'];
    }
}
