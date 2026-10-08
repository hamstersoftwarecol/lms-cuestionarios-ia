@foreach ($announcements ?? [] as $announcement)
    <div x-data="{
            show: true,
            key: 'lms-ann-{{ $announcement->id }}-{{ $announcement->updated_at->timestamp }}',
            init() { try { this.show = ! localStorage.getItem(this.key) } catch (e) {} },
            dismiss() { this.show = false; try { localStorage.setItem(this.key, 1) } catch (e) {} },
         }"
         x-show="show" x-transition
         class="flex items-start gap-3 rounded-2xl border px-4 py-3 {{ \App\Support\Palette::alert($announcement->type) }}">
        <x-heroicon-o-megaphone class="mt-0.5 h-5 w-5 shrink-0" />
        <div class="min-w-0 flex-1 text-sm">
            <p class="font-semibold">{{ $announcement->title }}</p>
            <p class="mt-0.5 whitespace-pre-line opacity-90">{{ $announcement->body }}</p>
        </div>
        <button type="button" class="shrink-0 rounded-lg p-1 opacity-70 hover:opacity-100" aria-label="Descartar anuncio" @click="dismiss()">
            <x-heroicon-o-x-mark class="h-4 w-4" />
        </button>
    </div>
@endforeach
