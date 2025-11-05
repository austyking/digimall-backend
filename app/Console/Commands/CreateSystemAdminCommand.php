<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

/**
 * Create a system administrator user with proper roles and permissions.
 */
final class CreateSystemAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-system-admin
                            {--name= : The name of the system administrator}
                            {--email= : The email address of the system administrator}
                            {--password= : The password for the system administrator}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a system administrator user with full platform access';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Creating System Administrator...');
        $this->newLine();

        // Get input from options or prompt
        $name = $this->option('name') ?: $this->ask('Name', 'System Administrator');
        $email = $this->option('email') ?: $this->ask('Email', 'admin@digimall.com');
        $password = $this->option('password') ?: $this->secret('Password (leave empty for default)') ?: 'Password123!';

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  • {$error}");
            }

            return self::FAILURE;
        }

        // Check if user already exists
        $existingUser = User::query()->where('email', $email)->first();
        if ($existingUser !== null) {
            $this->error("User with email '{$email}' already exists!");

            return self::FAILURE;
        }

        // Confirm action
        if (! $this->option('force')) {
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $name],
                    ['Email', $email],
                    ['Password', str_repeat('*', strlen($password))],
                    ['Role', 'System Administrator'],
                ]
            );

            if (! $this->confirm('Do you want to create this system administrator?', true)) {
                $this->warn('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            // Ensure the System Administrator role exists
            $role = Role::firstOrCreate(
                ['name' => 'system-administrator'],
                ['guard_name' => 'web']
            );

            // Create the user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            // Assign role
            $user->assignRole($role);

            $this->newLine();
            $this->info('✓ System Administrator created successfully!');
            $this->newLine();

            // Display credentials
            $this->line('<fg=green>Login Credentials:</>');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $user->name],
                    ['Email', $user->email],
                    ['Password', $password],
                    ['Role', 'System Administrator'],
                    ['User ID', $user->id],
                ]
            );

            $this->newLine();
            $this->warn('⚠ IMPORTANT: Store these credentials securely!');
            $this->warn('⚠ Change the default password after first login.');
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create system administrator:');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
