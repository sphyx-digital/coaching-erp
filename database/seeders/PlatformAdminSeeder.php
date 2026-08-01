<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * First-run seeder: creates exactly one Platform Admin. Idempotent - re-running
 * never creates a second admin or resets the password.
 */
class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('PLATFORM_ADMIN_EMAIL', 'admin@coaching.sphyx.in');

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = env('PLATFORM_ADMIN_PASSWORD') ?: Str::password(16);

        $user = User::create([
            'name' => 'Platform Admin',
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('Platform Admin');

        $this->command?->warn("Platform Admin created: {$email}");
        if (! env('PLATFORM_ADMIN_PASSWORD')) {
            $this->command?->warn("Generated password (shown once): {$password}");
        }
    }
}
