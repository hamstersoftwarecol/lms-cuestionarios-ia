<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'question_text', 'options', 'correct_answers', 'explanation', 'difficulty', 'position'])]
class Question extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'difficulty' => Difficulty::class,
            'options' => 'array',
            'correct_answers' => 'array',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Una respuesta es correcta solo si el conjunto seleccionado coincide exactamente
     * con el conjunto de respuestas correctas (aplica a MCQ y SATA).
     *
     * @param  array<int, int|string>  $selected
     */
    public function isCorrect(array $selected): bool
    {
        $normalize = function (array $values): array {
            $values = array_values(array_unique(array_map('intval', $values)));
            sort($values);

            return $values;
        };

        return $normalize($selected) === $normalize($this->correct_answers ?? []);
    }

    public function points(): int
    {
        return ($this->difficulty ?? Difficulty::Medium)->points();
    }
}
