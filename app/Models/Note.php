<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'folder_id', 'title', 'content', 'summary', 'source_type', 'original_filename',
    'file_path', 'mime_type', 'file_size', 'extraction_status', 'extraction_error', 'is_favorite',
])]
class Note extends Model
{
    use HasFactory;

    public const SOURCE_LABELS = [
        'text' => 'Texto',
        'pdf' => 'PDF',
        'image' => 'Imagen',
        'docx' => 'Word',
    ];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('title', 'like', "%{$term}%")
            ->orWhere('content', 'like', "%{$term}%"));
    }

    public function wordCount(): int
    {
        return str_word_count(strip_tags((string) $this->content), 0, 'áéíóúüñÁÉÍÓÚÜÑ');
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil($this->wordCount() / 200));
    }

    public function excerpt(int $limit = 160): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->content))), $limit);
    }

    public function sourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->source_type] ?? 'Texto';
    }

    public function hasContent(): bool
    {
        return filled(trim((string) $this->content));
    }
}
