<?php

namespace App\Enums;

enum AssessmentType: string
{
    case Practice = 'practice';
    case Pre = 'pre';
    case Post = 'post';

    public function label(): string
    {
        return match ($this) {
            self::Practice => 'Práctica',
            self::Pre => 'Evaluación previa',
            self::Post => 'Evaluación posterior',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Practice => 'Practica libremente para sumar puntos y mantener tu racha.',
            self::Pre => 'Mide lo que sabes antes de estudiar el material.',
            self::Post => 'Comprueba cuánto has aprendido después de estudiar.',
        };
    }
}
