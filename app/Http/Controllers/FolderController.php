<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FolderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules($request));

        $request->user()->folders()->create($validated);

        return back()->with('success', 'Carpeta creada.');
    }

    public function update(Request $request, Folder $folder): RedirectResponse
    {
        $this->authorize('update', $folder);

        $folder->update($request->validate($this->rules($request, $folder)));

        return back()->with('success', 'Carpeta actualizada.');
    }

    public function destroy(Folder $folder): RedirectResponse
    {
        $this->authorize('delete', $folder);

        // Las notas no se borran: quedan sin carpeta (nullOnDelete).
        $folder->delete();

        return redirect()->route('notes.index')->with('success', 'Carpeta eliminada. Sus notas siguen disponibles.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?Folder $folder = null): array
    {
        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('folders')->where('user_id', $request->user()->id)->ignore($folder)],
            'color' => ['required', Rule::in(Folder::COLORS)],
        ];
    }
}
