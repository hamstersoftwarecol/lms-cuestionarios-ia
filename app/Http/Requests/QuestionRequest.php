<?php

namespace App\Http\Requests;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Elimina las opciones vacías conservando el orden.
        $options = collect($this->input('options', []))->map(fn ($o) => trim((string) $o));
        $keep = $options->filter(fn ($o) => $o !== '')->keys();
        $remap = $keep->flip();

        $this->merge([
            'options' => $options->only($keep)->values()->all(),
            'correct_answers' => collect($this->input('correct_answers', []))
                ->map(fn ($i) => (int) $i)
                ->filter(fn ($i) => $remap->has($i))
                ->map(fn ($i) => $remap[$i])
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'question_text' => ['required', 'string', 'max:2000'],
            'options' => ['required', 'array', 'min:2', 'max:6'],
            'options.*' => ['required', 'string', 'max:500', 'distinct'],
            'correct_answers' => ['required', 'array', 'min:1'],
            'correct_answers.*' => ['integer', 'min:0'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'difficulty' => ['required', Rule::in(array_map(fn ($d) => $d->value, Difficulty::forQuestions()))],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $correct = $this->input('correct_answers', []);

                if ($this->input('type') === QuestionType::Mcq->value && count($correct) !== 1) {
                    $validator->errors()->add('correct_answers', 'Una pregunta de opción múltiple debe tener exactamente una respuesta correcta.');
                }

                if (collect($correct)->contains(fn ($i) => $i >= count($this->input('options', [])))) {
                    $validator->errors()->add('correct_answers', 'Las respuestas correctas deben corresponder a opciones existentes.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'correct_answers.required' => 'Marca al menos una respuesta correcta.',
            'options.min' => 'Añade al menos dos opciones.',
        ];
    }
}
