{{-- Campos compartidos: carpeta y etiquetas --}}
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="folder_id" value="Carpeta" />
        <select id="folder_id" name="folder_id" class="input">
            <option value="">Sin carpeta</option>
            @foreach ($folders as $folder)
                <option value="{{ $folder->id }}" @selected(old('folder_id', $note->folder_id ?? null) == $folder->id)>{{ $folder->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('folder_id')" />
    </div>
    <div>
        <x-input-label for="new_tags" value="Nuevas etiquetas (separadas por comas)" />
        <x-text-input id="new_tags" name="new_tags" :value="old('new_tags')" placeholder="biología, parcial" />
        <x-input-error :messages="$errors->get('new_tags')" />
    </div>
</div>

@if ($tags->isNotEmpty())
    @php($selectedTags = collect(old('tags', isset($note) ? $note->tags->pluck('id')->all() : []))->map(fn ($id) => (int) $id))
    <div>
        <x-input-label value="Etiquetas existentes" />
        <div class="flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                <label class="cursor-pointer">
                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}" class="peer sr-only" @checked($selectedTags->contains($tag->id))>
                    <span class="pill {{ \App\Support\Palette::pill('slate') }} peer-checked:bg-brand-600 peer-checked:text-white peer-checked:ring-brand-600">#{{ $tag->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
@endif
