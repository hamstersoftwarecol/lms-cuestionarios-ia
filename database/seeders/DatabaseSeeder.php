<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(BadgeSeeder::class);

        $admin = User::firstOrNew(['email' => config('lms.admin.email')]);
        $admin->forceFill([
            'name' => config('lms.admin.name'),
            'password' => config('lms.admin.password'),
            'role' => Role::Admin,
            'is_active' => true,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();

        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
        }
    }
}
