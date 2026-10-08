<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use RuntimeException;

/**
 * Copias de seguridad y restauración de la base de datos SQLite.
 *
 * Se usa `VACUUM INTO`, que genera una copia consistente sin bloquear la base de datos.
 */
class BackupService
{
    private const SQLITE_HEADER = "SQLite format 3\0";

    public function directory(): string
    {
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    public function isSupported(): bool
    {
        $connection = config('database.default');

        return config("database.connections.{$connection}.driver") === 'sqlite'
            && $this->databasePath() !== ':memory:';
    }

    public function databasePath(): string
    {
        $connection = config('database.default');

        return (string) config("database.connections.{$connection}.database");
    }

    public function create(string $label = 'manual'): string
    {
        $this->ensureSupported();

        $name = sprintf('backup-%s-%s.sqlite', now()->format('Y-m-d_His'), preg_replace('/[^a-z0-9-]/', '', $label) ?: 'manual');
        $path = $this->directory().DIRECTORY_SEPARATOR.$name;

        DB::statement('VACUUM INTO ?', [$path]);

        return $name;
    }

    /**
     * @return Collection<int, array{name: string, size: int, created_at: Carbon}>
     */
    public function list(): Collection
    {
        return collect(File::files($this->directory()))
            ->filter(fn ($file) => $file->getExtension() === 'sqlite')
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => Carbon::createFromTimestamp($file->getMTime()),
            ])
            ->sortByDesc('created_at')
            ->values();
    }

    public function path(string $name): string
    {
        if ($name !== basename($name) || ! str_ends_with($name, '.sqlite')) {
            throw new InvalidArgumentException('Nombre de copia no válido.');
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$name;

        if (! File::exists($path)) {
            throw new InvalidArgumentException('La copia de seguridad no existe.');
        }

        return $path;
    }

    public function delete(string $name): void
    {
        File::delete($this->path($name));
    }

    public function storeUpload(UploadedFile $file): string
    {
        if (! $this->isSqliteFile($file->getRealPath())) {
            throw new InvalidArgumentException('El archivo no es una base de datos SQLite válida.');
        }

        $name = sprintf('backup-%s-subida.sqlite', now()->format('Y-m-d_His'));
        $file->move($this->directory(), $name);

        return $name;
    }

    /**
     * Restaura una copia. Antes se guarda automáticamente el estado actual por seguridad.
     */
    public function restore(string $name): string
    {
        $this->ensureSupported();
        $source = $this->path($name);

        if (! $this->isSqliteFile($source)) {
            throw new InvalidArgumentException('La copia seleccionada está dañada o no es SQLite.');
        }

        $safety = $this->create('pre-restore');
        $target = $this->databasePath();

        DB::disconnect();

        foreach (['-wal', '-shm'] as $suffix) {
            File::delete($target.$suffix);
        }

        if (! File::copy($source, $target)) {
            throw new RuntimeException('No se pudo restaurar la copia de seguridad.');
        }

        DB::reconnect();

        return $safety;
    }

    /**
     * Elimina las copias automáticas más antiguas conservando las N más recientes.
     */
    public function prune(int $keep): int
    {
        $old = $this->list()->slice($keep);
        $old->each(fn (array $backup) => File::delete($this->directory().DIRECTORY_SEPARATOR.$backup['name']));

        return $old->count();
    }

    private function isSqliteFile(string $path): bool
    {
        $handle = @fopen($path, 'rb');

        if (! $handle) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        return $header === self::SQLITE_HEADER;
    }

    private function ensureSupported(): void
    {
        if (! $this->isSupported()) {
            throw new RuntimeException('Las copias de seguridad solo están disponibles con una base de datos SQLite en archivo.');
        }
    }
}
