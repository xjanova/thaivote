<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin
                            {--name= : The name of the admin user}
                            {--email= : The email of the admin user}
                            {--password= : The password for the admin user}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║       Create Admin User                ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->info('');

        // Get name
        $name = $this->option('name');
        if (empty($name)) {
            $name = $this->ask('Enter admin name');
        }

        if (empty($name)) {
            $this->error('Name is required');
            return self::FAILURE;
        }

        // Get email
        $email = $this->option('email');
        if (empty($email)) {
            $email = $this->ask('Enter admin email');
        }

        if (empty($email)) {
            $this->error('Email is required');
            return self::FAILURE;
        }

        // Validate email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid email format');
            return self::FAILURE;
        }

        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email '{$email}' already exists");

            if (! $this->option('force') && ! $this->confirm('Do you want to update the existing user to admin?', false)) {
                return self::FAILURE;
            }

            $user = User::where('email', $email)->first();
            $this->updateUserToAdmin($user);
            $this->info('');
            $this->info("User '{$name}' has been updated to admin!");
            return self::SUCCESS;
        }

        // Get password
        $password = $this->option('password');
        if (empty($password)) {
            $password = $this->secret('Enter admin password (min 8 characters)');
        }

        if (empty($password)) {
            $this->error('Password is required');
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters');
            return self::FAILURE;
        }

        // Confirm password if interactive
        if (empty($this->option('password')) && ! $this->option('force')) {
            $confirmPassword = $this->secret('Confirm password');
            if ($password !== $confirmPassword) {
                $this->error('Passwords do not match');
                return self::FAILURE;
            }
        }

        // Create user
        try {
            $userData = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ];

            // Add is_admin if the column exists
            if ($this->hasAdminColumn()) {
                $userData['is_admin'] = true;
            }

            $user = User::create($userData);

            // Assign admin role if using spatie/laravel-permission
            $this->assignAdminRole($user);

            $this->info('');
            $this->info('╔════════════════════════════════════════╗');
            $this->info('║   Admin user created successfully!     ║');
            $this->info('╚════════════════════════════════════════╝');
            $this->info('');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $name],
                    ['Email', $email],
                    ['Admin', 'Yes'],
                ]
            );
            $this->info('');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create admin user: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Check if is_admin column exists in users table.
     */
    private function hasAdminColumn(): bool
    {
        try {
            return \Schema::hasColumn('users', 'is_admin');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Assign admin role if using spatie/laravel-permission.
     */
    private function assignAdminRole(User $user): void
    {
        try {
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
                $user->assignRole($adminRole);
                $this->info('Assigned "admin" role to user');
            }
        } catch (\Exception $e) {
            // Silently ignore if roles table doesn't exist
        }
    }

    /**
     * Update existing user to admin.
     */
    private function updateUserToAdmin(User $user): void
    {
        if ($this->hasAdminColumn()) {
            $user->is_admin = true;
            $user->save();
        }

        $this->assignAdminRole($user);
    }
}
