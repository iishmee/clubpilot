<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupLocalTestAccounts extends Command
{
    protected $signature = 'clubpilot:setup-local-test-accounts';

    protected $description = 'Create local ClubPilot organizer and administrator test accounts.';

    public function handle(): int
    {
        if (! in_array(config('app.env'), ['local', 'testing'], true)) {
            $this->error('Test accounts may only be set up when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $accounts = [];

        foreach ([
            'organizer' => 'Club Organizer',
            'admin' => 'University Administrator',
        ] as $role => $label) {
            $name = trim((string) $this->ask("Name for {$label}"));
            $email = strtolower(trim((string) $this->ask("Email for {$label}")));

            if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $this->error("A name and valid email address are required for {$label}.");

                return self::FAILURE;
            }

            $existing = User::query()->where('email', $email)->first();

            if ($existing && $existing->role !== $role) {
                $this->error("{$email} already belongs to an account with a different role. No accounts were changed.");

                return self::FAILURE;
            }

            $accounts[$role] = [
                'name' => $name,
                'email' => $email,
                'existing' => $existing !== null,
            ];
        }

        if ($accounts['organizer']['email'] === $accounts['admin']['email']) {
            $this->error('The organizer and administrator must use different email addresses.');

            return self::FAILURE;
        }

        foreach ($accounts as $role => &$account) {
            if ($account['existing']) {
                continue;
            }

            $password = $this->secret("Password for {$account['email']} (minimum 12 characters)");
            $confirmation = $this->secret("Confirm password for {$account['email']}");

            if (strlen((string) $password) < 12 || $password !== $confirmation) {
                $this->error('Passwords must match and be at least 12 characters. No accounts were created.');

                return self::FAILURE;
            }

            $account['password'] = $password;
        }
        unset($account);

        DB::transaction(function () use ($accounts): void {
            foreach ($accounts as $role => $account) {
                if ($account['existing']) {
                    continue;
                }

                $user = new User([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $account['password'],
                ]);
                $user->role = $role;
                $user->save();
            }
        });

        foreach ($accounts as $role => $account) {
            $this->info($account['existing']
                ? "{$role}: {$account['email']} already exists; left unchanged."
                : "{$role}: {$account['email']} created.");
        }

        return self::SUCCESS;
    }
}
