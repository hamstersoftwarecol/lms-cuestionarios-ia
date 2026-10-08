<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::with('author:id,name')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.announcements.form', ['announcement' => new Announcement(['type' => 'info', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $announcement = new Announcement($this->validated($request));
        $announcement->author()->associate($request->user())->save();

        if ($request->boolean('notify') && $announcement->is_active) {
            User::where('is_active', true)->chunkById(200, fn ($users) => Notification::send($users, new AnnouncementPublished($announcement)));
        }

        ActivityLogger::log('admin.announcement_created', "Publicó el anuncio «{$announcement->title}»", $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Anuncio publicado.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.form', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));
        ActivityLogger::log('admin.announcement_updated', "Editó el anuncio «{$announcement->title}»", $announcement);

        return redirect()->route('admin.announcements.index')->with('success', 'Anuncio actualizado.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        ActivityLogger::log('admin.announcement_deleted', "Eliminó el anuncio «{$announcement->title}»");
        $announcement->delete();

        return back()->with('success', 'Anuncio eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['required', Rule::in(array_keys(Announcement::TYPES))],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        return $validated + ['is_active' => $request->boolean('is_active')];
    }
}
