<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $note = $this->route('note');

        return $note === null || $this->user()->can('update', $note);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;
        $creating = $this->isMethod('post');

        return [
            'title' => [$creating ? 'nullable' : 'required', 'string', 'max:255'],
            'content' => [$creating ? 'required_without:document' : 'required', 'nullable', 'string', 'max:500000'],
            'document' => [
                'nullable',
                'file',
                'max:'.config('lms.max_upload_kb'),
                'extensions:'.implode(',', config('lms.allowed_extensions')),
            ],
            'folder_id' => ['nullable', Rule::exists('folders', 'id')->where('user_id', $userId)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', Rule::exists('tags', 'id')->where('user_id', $userId)],
            'new_tags' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required_without' => 'Escribe el contenido o sube un documento.',
            'document.uploaded' => 'No se pudo subir el archivo: probablemente supera upload_max_filesize/post_max_size de PHP.',
            'document.extensions' => 'Formato no admitido. Usa PDF, imagen (JPG, PNG, WEBP, HEIC), Word (.docx) o texto.',
        ];
    }

    /**
     * IDs de etiquetas existentes más las nuevas escritas separadas por comas.
     *
     * @return array<int, int>
     */
    public function tagIds(): array
    {
        $ids = collect($this->input('tags', []))->map(fn ($id) => (int) $id);

        $new = collect(explode(',', (string) $this->input('new_tags')))
            ->map(fn (string $name) => mb_strtolower(trim($name)))
            ->filter()
            ->unique()
            ->take(10)
            ->map(fn (string $name) => $this->user()->tags()->firstOrCreate(['name' => mb_substr($name, 0, 50)])->id);

        return $ids->merge($new)->unique()->values()->all();
    }
}
