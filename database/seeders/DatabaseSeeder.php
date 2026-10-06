<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * What a fresh install needs to be usable: one account that can sign in to the
 * admin. Nothing else — the demonstration catalogue is DemoSeeder, and a real
 * shop does not want it.
 *
 *   php artisan db:seed                      the owner's account
 *   php artisan db:seed --class=DemoSeeder    a shop full of example sarees
 *
 * Set ADMIN_EMAIL and ADMIN_PASSWORD in .env before running this on a live
 * site. Running it twice is safe; it never resets a password that already
 * exists.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('shop.admin.email');

        $admin = User::withTrashed()->firstWhere('email', $email);

        if ($admin) {
            $admin->forceFill(['is_admin' => true, 'deleted_at' => null])->save();
            $this->command?->info("Admin already exists: {$email}");

            return;
        }

        User::create([
            'name' => (string) config('shop.admin.name'),
            'email' => $email,
            'password' => (string) config('shop.admin.password'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Admin created: {$email}");
    }
}
