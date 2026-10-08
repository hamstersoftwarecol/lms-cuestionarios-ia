<section>
    <header>
        <h2 class="card-title">Preferencias</h2>
        <p class="mt-1 text-sm muted">Personaliza el tema y la voz de lectura con IA.</p>
    </header>

    <form method="POST" action="{{ route('preferences.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('PATCH')

        <div>
            <p class="label">Tema</p>
            <div class="grid grid-cols-3 gap-3">
                @foreach (['light' => ['☀️', 'Claro'], 'dark' => ['🌙', 'Oscuro'], 'system' => ['💻', 'Sistema']] as $value => [$icon, $label])
                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="{{ $value }}" class="peer sr-only" @checked(old('theme', $user->theme) === $value)>
                        <div class="rounded-xl border border-slate-200 p-3 text-center text-sm transition peer-checked:border-brand-500 peer-checked:ring-2 peer-checked:ring-brand-500/30 dark:border-slate-700">
                            <div class="text-xl">{{ $icon }}</div>{{ $label }}
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <x-input-label for="tts_voice" value="Voz de texto a voz (Gemini)" />
            <div class="flex flex-wrap items-center gap-2">
                <select id="tts_voice" name="tts_voice" class="input sm:w-auto">
                    @foreach ($voices as $key => $label)
                        <option value="{{ $key }}" @selected(old('tts_voice', $user->tts_voice) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-tts-button text="Hola, esta es una muestra de la voz que leerá tus apuntes y preguntas." />
            </div>
            <x-input-error :messages="$errors->get('tts_voice')" />
        </div>

        <x-primary-button>Guardar preferencias</x-primary-button>
    </form>
</section>
