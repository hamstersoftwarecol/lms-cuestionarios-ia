<?php

namespace App\Enums;

enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Fácil',
            self::Medium => 'Media',
            self::Hard => 'Difícil',
            self::Mixed => 'Mixta',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Easy => 'emerald',
            self::Medium => 'amber',
            self::Hard => 'rose',
            self::Mixed => 'indigo',
        };
    }

    /** Puntos que otorga una respuesta correcta de esta dificultad. */
    public function points(): int
    {
        return match ($this) {
            self::Easy => 10,
            self::Medium => 15,
            self::Hard => 20,
            self::Mixed => 15,
        };
    }

    /** @return array<int, self> */
    public static function forQuestions(): array
    {
        return [self::Easy, self::Medium, self::Hard];
    }
}
