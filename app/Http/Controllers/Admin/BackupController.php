<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(): View
    {
        return view('admin.backups.index', [
            'supported' => $this->backups->isSupported(),
            'backups' => $this->backups->isSupported() ? $this->backups->list() : collect(),
            'databaseSize' => $this->backups->isSupported() && is_file($this->backups->databasePath()) ? filesize($this->backups->databasePath()) : null,
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $name = $this->backups->create();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLogger::log('admin.backup_created', "Creó la copia de seguridad {$name}");

        return back()->with('success', "Copia creada: {$name}");
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate(['backup' => ['required', 'file', 'max:512000']]);

        try {
            $name = $this->backups->storeUpload($request->file('backup'));
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLogger::log('admin.backup_uploaded', "Subió la copia de seguridad {$name}");

        return back()->with('success', "Copia subida: {$name}. Ya puedes restaurarla.");
    }

    public function download(string $backup): BinaryFileResponse
    {
        try {
            $path = $this->backups->path($backup);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        ActivityLogger::log('admin.backup_downloaded', "Descargó la copia de seguridad {$backup}");

        return response()->download($path);
    }

    public function restore(Request $request, string $backup): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:RESTAURAR']], [
            'confirmation.in' => 'Escribe RESTAURAR para confirmar.',
        ]);

        $admin = $request->user();

        try {
            $safety = $this->backups->restore($backup);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // La base de datos restaurada puede no contener la sesión actual: se registra tras restaurar.
        ActivityLogger::log('admin.backup_restored', "Restauró la copia {$backup} (copia previa: {$safety})", user: Auth::user() ?? $admin);

        return redirect()->route('admin.backups.index')->with('success', "Base de datos restaurada desde {$backup}. Se guardó una copia previa: {$safety}");
    }

    public function destroy(string $backup): RedirectResponse
    {
        try {
            $this->backups->delete($backup);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        ActivityLogger::log('admin.backup_deleted', "Eliminó la copia de seguridad {$backup}");

        return back()->with('success', 'Copia eliminada.');
    }
}
