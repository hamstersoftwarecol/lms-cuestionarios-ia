<?php

namespace App\Enums;

enum TaskType: string
{
    case Study = 'study';
    case Quiz = 'quiz';
    case Review = 'review';
    case Mock = 'mock';
    case Exam = 'exam';

    public function label(): string
    {
        return match ($this) {
            self::Study => 'Estudio',
            self::Quiz => 'Cuestionario',
            self::Review => 'Repaso',
            self::Mock => 'Simulacro',
            self::Exam => 'Examen',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Study => '📖',
            self::Quiz => '📝',
            self::Review => '🔁',
            self::Mock => '🎯',
            self::Exam => '🏁',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Study => 'sky',
            self::Quiz => 'indigo',
            self::Review => 'amber',
            self::Mock => 'rose',
            self::Exam => 'emerald',
        };
    }
}
