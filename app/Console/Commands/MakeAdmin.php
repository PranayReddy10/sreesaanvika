<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as askPassword;
use function Laravel\Prompts\text;

/**
 * Make somebody an admin, or give them a new password.
 *
 * The way back in when the admin password is wrong, forgotten, or — as the
 * seeder used to allow — never really set. Deliberately a command of its own
 * rather than a note telling the shop to open tinker: a shop owner locked out
 * of their own till at ten at night should need one line, not a REPL.
 *
 *   php artisan ojasvi:admin
 *   php artisan ojasvi:admin --email=you@ojasvidrapes.in --password=...
 */
class MakeAdmin extends Command
{
    protected $signature = 'ojasvi:admin
                            {--email= : The address they sign in with}
                            {--password= : Their new password; you are asked if this is left out}
                            {--name= : Their name, for a new account}';

    protected $description = 'Create an admin account, or set the password on one that already exists';

    public function handle(): int
    {
        $email = $this->option('email')
            ?: text('Email', default: (string) config('shop.admin.email'), required: true);

        $password = $this->option('password')
            // Asked for rather than typed on the command line by default: a
            // password given as an argument is kept in the shell's history.
            ?: askPassword('New password', required: true);

        // Checked before anything else is asked. Asking for a name and then
        // rejecting the password wastes the one thing the person running this
        // has least of, which is patience with a shop that will not let them in.
        if ($this->rejects(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ])) {
            return self::FAILURE;
        }

        $existing = User::withTrashed()->firstWhere('email', $email);

        $name = $this->option('name')
            ?: ($existing?->name ?: text('Name', default: (string) config('shop.admin.name'), required: true));

        if ($this->rejects(['name' => $name], ['name' => ['required', 'string', 'max:140']])) {
            return self::FAILURE;
        }

        if ($existing) {
            $existing->forceFill([
                'password' => $password,
                'is_admin' => true,
                'deleted_at' => null,
            ])->save();

            $this->info("Password set for {$email}. They can use the admin.");
        } else {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);

            $this->info("Admin created: {$email}");
        }

        $this->line('Sign in at ' . rtrim((string) config('app.url'), '/') . '/admin');

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $values */
    private function rejects(array $values, array $rules): bool
    {
        $check = Validator::make($values, $rules, [
            'password.min' => 'The password must be at least 8 characters — this one opens the till.',
        ]);

        foreach ($check->errors()->all() as $problem) {
            $this->error($problem);
        }

        return $check->fails();
    }
}
