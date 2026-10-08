<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('tags')->where('user_id', $request->user()->id)],
        ]);

        $request->user()->tags()->create(['name' => mb_strtolower(trim($validated['name']))]);

        return back()->with('success', 'Etiqueta creada.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return redirect()->route('notes.index')->with('success', 'Etiqueta eliminada.');
    }
}
