<x-app-layout :title="$group->name">
    <x-page-header :title="$group->name" :subtitle="$group->description">
        @if ($canManage)
            <x-confirm-form :action="route('groups.destroy', $group)" method="DELETE" message="¿Eliminar el grupo para todos sus miembros?">
                <button class="btn-ghost text-rose-600"><x-heroicon-o-trash class="h-4 w-4" /> Eliminar grupo</button>
            </x-confirm-form>
        @else
            <x-confirm-form :action="route('groups.leave', $group)" message="¿Salir de este grupo?">
                <button class="btn-secondary">Salir del grupo</button>
            </x-confirm-form>
        @endif
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Clasificación del grupo --}}
            <div class="card">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                    <h2 class="card-title">🏆 Clasificación del grupo</h2>
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs dark:bg-slate-800">
                        @foreach (\App\Services\LeaderboardService::PERIODS as $key => $label)
                            <a href="{{ route('groups.show', [$group, 'period' => $key]) }}" @class(['rounded-lg px-3 py-1 font-medium', 'bg-white shadow dark:bg-slate-700' => $period === $key, 'muted' => $period !== $key])>{{ $label }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($ranking as $row)
                        <div @class(['flex items-center gap-3 px-6 py-3', 'bg-brand-50/60 dark:bg-brand-500/5' => $row->user->id === auth()->id()])>
                            <span class="w-8 text-center font-bold">{{ $row->rank <= 3 ? ['🥇', '🥈', '🥉'][$row->rank - 1] : $row->rank }}</span>
                            <x-avatar :user="$row->user" class="h-9 w-9" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $row->user->name }}</p>
                                <p class="text-xs muted">{{ $row->quizzes }} cuestionarios · media {{ $row->avg_score }}%</p>
                            </div>
                            <span class="font-bold text-amber-600">{{ number_format($row->points) }} pts</span>
                        </div>
                    @empty
                        <p class="px-6 py-8 text-center text-sm muted">Nadie ha sumado puntos en este periodo todavía.</p>
                    @endforelse
                </div>
            </div>

            {{-- Cuestionarios compartidos --}}
            <div class="card card-body">
                <h2 class="card-title">📝 Cuestionarios compartidos</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($quizzes as $quiz)
                        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center dark:border-slate-700">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('quizzes.show', $quiz) }}" class="font-medium hover:text-brand-600">{{ $quiz->title }}</a>
                                <p class="text-xs muted">{{ $quiz->questions_count }} preguntas · por {{ $quiz->user->name }} · {{ $quiz->pivot->created_at?->diffForHumans() }}</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('attempts.create', $quiz) }}" class="btn-primary btn-sm"><x-heroicon-o-play class="h-4 w-4" /> Practicar</a>
                                @if ($quiz->user_id === auth()->id() || $canManage)
                                    <x-confirm-form :action="route('groups.quizzes.destroy', [$group, $quiz])" method="DELETE" message="¿Retirar este cuestionario del grupo?">
                                        <button class="btn-ghost btn-sm" aria-label="Retirar"><x-heroicon-o-x-mark class="h-4 w-4" /></button>
                                    </x-confirm-form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm muted">Todavía no se ha compartido ningún cuestionario.</p>
                    @endforelse
                </div>

                @if ($myQuizzes->isNotEmpty())
                    <form method="POST" action="{{ route('groups.quizzes.store', $group) }}" class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row dark:border-slate-800">
                        @csrf
                        <select name="quiz_id" class="input" required>
                            <option value="">Comparte uno de tus cuestionarios…</option>
                            @foreach ($myQuizzes as $quiz)
                                <option value="{{ $quiz->id }}">{{ $quiz->title }}</option>
                            @endforeach
                        </select>
                        <button class="btn-secondary shrink-0"><x-heroicon-o-share class="h-4 w-4" /> Compartir</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="card card-body text-center">
                <p class="text-sm muted">Código de invitación</p>
                <p class="mt-2 font-mono text-3xl font-bold tracking-widest">{{ $group->formattedCode() }}</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <button type="button" x-data @click="$copy(@js($group->formattedCode()))" class="btn-secondary btn-sm"><x-heroicon-o-document-duplicate class="h-4 w-4" /> Copiar código</button>
                    <button type="button" x-data @click="$copy(@js(route('groups.index', ['code' => $group->invite_code])))" class="btn-secondary btn-sm"><x-heroicon-o-link class="h-4 w-4" /> Copiar enlace</button>
                </div>
                @if ($canManage)
                    <x-confirm-form :action="route('groups.code', $group)" message="El código actual dejará de funcionar. ¿Generar uno nuevo?" class="mt-3">
                        <button class="text-xs link">Generar código nuevo</button>
                    </x-confirm-form>
                @endif
            </div>

            <div class="card card-body">
                <h2 class="card-title">👥 Miembros ({{ $members->count() }}/{{ $group->max_members }})</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($members as $member)
                        <li class="flex items-center gap-3">
                            <x-avatar :user="$member" class="h-8 w-8" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $member->name }}</p>
                                <p class="text-xs muted">{{ $member->pivot->role === 'owner' ? 'Propietario' : 'Miembro' }} · {{ number_format($member->points) }} pts</p>
                            </div>
                            @if ($canManage && $member->pivot->role !== 'owner')
                                <x-confirm-form :action="route('groups.members.destroy', [$group, $member])" method="DELETE" :message="'¿Expulsar a '.$member->name.' del grupo?'">
                                    <button class="btn-ghost p-1 text-rose-600" aria-label="Expulsar"><x-heroicon-o-x-mark class="h-4 w-4" /></button>
                                </x-confirm-form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($canManage)
                <form method="POST" action="{{ route('groups.update', $group) }}" class="card card-body space-y-3">
                    @csrf @method('PUT')
                    <h2 class="card-title">⚙️ Ajustes del grupo</h2>
                    <x-text-input name="name" :value="$group->name" required maxlength="100" />
                    <textarea name="description" rows="2" class="input" maxlength="500">{{ $group->description }}</textarea>
                    <div>
                        <x-input-label for="max_members" value="Máximo de miembros" />
                        <x-text-input id="max_members" type="number" name="max_members" :value="$group->max_members" min="2" max="500" required />
                    </div>
                    <button class="btn-secondary w-full">Guardar</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
