<?php

namespace App\Http\Controllers;

use App\Services\Ai\TextToSpeech;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
            'tts_voice' => ['required', Rule::in(array_keys(TextToSpeech::voices()))],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'preferences-updated');
    }

    /**
     * Cambio rápido de tema desde la barra superior (petición AJAX).
     */
    public function theme(Request $request): Response
    {
        $validated = $request->validate(['theme' => ['required', Rule::in(['light', 'dark', 'system'])]]);

        $request->user()->update($validated);

        return response()->noContent();
    }
}
