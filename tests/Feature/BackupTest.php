<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Usa una base de datos SQLite en archivo temporal (VACUUM INTO no tiene sentido en memoria).
 */
class BackupTest extends TestCase
{
    private string $database;

    private string $backups;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = sys_get_temp_dir().'/lms-test-'.uniqid().'.sqlite';
        touch($this->database);
        config(['database.connections.sqlite.database' => $this->database]);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);

        $this->backups = storage_path('app/backups');
        File::deleteDirectory($this->backups);
    }

    protected function tearDown(): void
    {
        DB::disconnect();
        File::delete($this->database);
        File::deleteDirectory($this->backups);

        parent::tearDown();
    }

    public function test_admin_creates_downloads_and_restores_a_backup(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'antes@example.com']);

        $this->actingAs($admin)->post(route('admin.backups.store'))->assertSessionHas('success');
        $name = app(BackupService::class)->list()->first()['name'];
        $this->assertStringStartsWith('backup-', $name);

        $this->get(route('admin.backups.download', $name))->assertOk()->assertDownload($name);

        // Cambios posteriores a la copia que la restauración debe deshacer.
        User::factory()->create(['email' => 'despues@example.com']);

        $this->post(route('admin.backups.restore', $name), ['confirmation' => 'no'])->assertSessionHasErrors('confirmation');
        $this->post(route('admin.backups.restore', $name), ['confirmation' => 'RESTAURAR'])->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'antes@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'despues@example.com']);
        $this->assertTrue(app(BackupService::class)->list()->contains(fn ($b) => str_contains($b['name'], 'pre-restore')));
    }

    public function test_path_traversal_and_invalid_uploads_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/backups/..%2F..%2F.env')->assertNotFound();

        $this->post(route('admin.backups.upload'), [
            'backup' => UploadedFile::fake()->createWithContent('malo.sqlite', 'esto no es sqlite'),
        ])->assertSessionHas('error');
    }

    public function test_the_scheduled_command_creates_and_prunes_backups(): void
    {
        foreach (range(1, 3) as $i) {
            $this->artisan('lms:backup', ['--keep' => 2])->assertSuccessful();
            $this->travel(1)->seconds();
        }

        $this->assertCount(2, app(BackupService::class)->list());
    }
}
