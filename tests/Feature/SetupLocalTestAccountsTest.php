<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupLocalTestAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_command_creates_an_organizer_and_admin_with_hidden_password_prompts(): void
    {
        $this->artisan('clubpilot:setup-local-test-accounts')
            ->expectsQuestion('Name for Club Organizer', 'Local Organizer')
            ->expectsQuestion('Email for Club Organizer', 'organizer@clubpilot.test')
            ->expectsQuestion('Name for University Administrator', 'Local Administrator')
            ->expectsQuestion('Email for University Administrator', 'admin@clubpilot.test')
            ->expectsQuestion('Password for organizer@clubpilot.test (minimum 12 characters)', 'organizer-passphrase')
            ->expectsQuestion('Confirm password for organizer@clubpilot.test', 'organizer-passphrase')
            ->expectsQuestion('Password for admin@clubpilot.test (minimum 12 characters)', 'admin-passphrase')
            ->expectsQuestion('Confirm password for admin@clubpilot.test', 'admin-passphrase')
            ->expectsOutput('organizer: organizer@clubpilot.test created.')
            ->expectsOutput('admin: admin@clubpilot.test created.')
            ->assertExitCode(0);

        $organizer = User::query()->where('email', 'organizer@clubpilot.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@clubpilot.test')->firstOrFail();

        $this->assertSame('organizer', $organizer->role);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('organizer-passphrase', $organizer->password));
        $this->assertTrue(Hash::check('admin-passphrase', $admin->password));
    }

    public function test_repeated_local_setup_does_not_change_existing_accounts_or_passwords(): void
    {
        $organizer = User::factory()->create([
            'name' => 'Existing Organizer',
            'email' => 'organizer@clubpilot.test',
            'password' => 'original-organizer-password',
            'role' => 'organizer',
        ]);
        $admin = User::factory()->create([
            'name' => 'Existing Admin',
            'email' => 'admin@clubpilot.test',
            'password' => 'original-admin-password',
            'role' => 'admin',
        ]);

        $this->artisan('clubpilot:setup-local-test-accounts')
            ->expectsQuestion('Name for Club Organizer', 'Replacement Organizer')
            ->expectsQuestion('Email for Club Organizer', 'organizer@clubpilot.test')
            ->expectsQuestion('Name for University Administrator', 'Replacement Administrator')
            ->expectsQuestion('Email for University Administrator', 'admin@clubpilot.test')
            ->expectsOutput('organizer: organizer@clubpilot.test already exists; left unchanged.')
            ->expectsOutput('admin: admin@clubpilot.test already exists; left unchanged.')
            ->assertExitCode(0);

        $this->assertSame('Existing Organizer', $organizer->fresh()->name);
        $this->assertTrue(Hash::check('original-organizer-password', $organizer->fresh()->password));
        $this->assertSame('Existing Admin', $admin->fresh()->name);
        $this->assertTrue(Hash::check('original-admin-password', $admin->fresh()->password));
    }

    public function test_command_refuses_to_run_in_production(): void
    {
        config(['app.env' => 'production']);

        $this->artisan('clubpilot:setup-local-test-accounts')
            ->expectsOutput('Test accounts may only be set up when APP_ENV is local or testing.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_account_with_a_different_role_is_never_promoted(): void
    {
        $member = User::factory()->create([
            'email' => 'member@clubpilot.test',
            'role' => 'member',
        ]);

        $this->artisan('clubpilot:setup-local-test-accounts')
            ->expectsQuestion('Name for Club Organizer', 'Organizer')
            ->expectsQuestion('Email for Club Organizer', 'member@clubpilot.test')
            ->expectsOutput('member@clubpilot.test already belongs to an account with a different role. No accounts were changed.')
            ->assertExitCode(1);

        $this->assertSame('member', $member->fresh()->role);
        $this->assertDatabaseCount('users', 1);
    }
}
