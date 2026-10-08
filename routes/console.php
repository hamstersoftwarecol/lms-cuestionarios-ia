<?php

use App\Services\BackupService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lms:backup {--keep= : Número de copias que se conservan}', function (BackupService $backups) {
    if (! $backups->isSupported()) {
        $this->error('Las copias de seguridad solo están disponibles con SQLite en archivo.');

        return 1;
    }

    $name = $backups->create('auto');
    $pruned = $backups->prune((int) ($this->option('keep') ?: config('lms.backups.keep')));

    $this->info("Copia creada: {$name}. Copias antiguas eliminadas: {$pruned}.");

    return 0;
})->purpose('Crea una copia de seguridad de la base de datos SQLite y elimina las antiguas');

Schedule::command('lms:backup')->dailyAt('02:00');
Schedule::command('model:prune')->daily();
