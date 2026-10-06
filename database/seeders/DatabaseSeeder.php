<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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
        if (! $this->tablesExist()) {
            return;
        }

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

    /**
     * Has anybody run the migrations yet?
     *
     * Asked plainly, because without it the first command somebody runs on a
     * new server answers with SQLSTATE[42S02] and a table name — which tells a
     * shop owner nothing at all, least of all that the fix is one command they
     * have not run yet.
     */
    private function tablesExist(): bool
    {
        if (Schema::hasTable('users')) {
            return true;
        }

        $this->command?->getOutput()->writeln('');
        $this->command?->error('  The database is empty — the tables have not been made yet.');
        $this->command?->getOutput()->writeln('');
        $this->command?->line('  Run this first, then try again:');
        $this->command?->getOutput()->writeln('');
        $this->command?->line('      <fg=yellow>php artisan migrate --force</>');
        $this->command?->getOutput()->writeln('');
        $this->command?->line('  <fg=gray>Seeding puts the first rows in. Migrating makes the tables</>');
        $this->command?->line('  <fg=gray>for them to go in, so it always comes first.</>');
        $this->command?->getOutput()->writeln('');

        return false;
    }
}
