<?php

namespace App\Enums;

enum QuestionType: string
{
    /** Opción múltiple con una única respuesta correcta. */
    case Mcq = 'mcq';

    /** Selecciona todas las que apliquen (varias respuestas correctas). */
    case Sata = 'sata';

    public function label(): string
    {
        return match ($this) {
            self::Mcq => 'Opción múltiple',
            self::Sata => 'Selección múltiple',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Mcq => 'MCQ',
            self::Sata => 'SATA',
        };
    }
}
