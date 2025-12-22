<?php

namespace SavyApps\LaravelStudio\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use SavyApps\LaravelStudio\Models\Role;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'studio:create-admin
                            {--name= : The name of the admin user}
                            {--email= : The email address of the admin user}
                            {--password= : The password for the admin user}
                            {--super : Create a super admin instead of regular admin}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new admin user for the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isSuperAdmin = $this->option('super');
        $roleLabel = $isSuperAdmin ? 'Super Admin' : 'Admin';

        $this->components->info("Create New {$roleLabel}");
        $this->newLine();

        // Get user model from config
        $userModel = config('studio.authorization.models.user', \App\Models\User::class);

        if (!class_exists($userModel)) {
            $this->components->error("User model '{$userModel}' not found.");
            $this->line('Please ensure the model exists or configure it in config/studio.php');
            $this->line('  studio.authorization.models.user');

            return self::FAILURE;
        }

        // Collect user details
        $name = $this->option('name') ?? $this->ask('Name');
        $email = $this->option('email') ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Password (min 8 characters)');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->components->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        // Check if email already exists
        if ($userModel::where('email', $email)->exists()) {
            $this->components->warn("A user with email '{$email}' already exists.");

            if (!$this->confirm('Do you want to update this user to an admin?', false)) {
                return self::FAILURE;
            }

            return $this->updateExistingUser($userModel, $email, $name, $password, $isSuperAdmin);
        }

        // Determine role
        $role = $isSuperAdmin ? Role::SUPER_ADMIN : Role::ADMIN;

        // Create the user
        try {
            $user = $userModel::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            // Assign role
            if (method_exists($user, 'assignRole')) {
                $user->assignRole($role);
            } else {
                $this->components->warn('User model does not support roles. Skipping role assignment.');
                $this->line('Make sure your User model uses the HasRoles trait.');
            }

            $this->newLine();
            $this->components->info("{$roleLabel} created successfully!");
            $this->newLine();

            $this->components->twoColumnDetail('Name', $name);
            $this->components->twoColumnDetail('Email', $email);
            $this->components->twoColumnDetail('Role', $role);

            $this->newLine();
            $this->line('<fg=gray>You can now log in with these credentials.</>');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error('Failed to create admin: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Update an existing user to admin.
     */
    protected function updateExistingUser(string $userModel, string $email, string $name, string $password, bool $isSuperAdmin): int
    {
        $role = $isSuperAdmin ? Role::SUPER_ADMIN : Role::ADMIN;
        $roleLabel = $isSuperAdmin ? 'Super Admin' : 'Admin';

        try {
            $user = $userModel::where('email', $email)->first();

            $user->update([
                'name' => $name,
                'password' => Hash::make($password),
            ]);

            // Assign role
            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([$role]);
            } elseif (method_exists($user, 'assignRole')) {
                if (method_exists($user, 'roles')) {
                    $user->roles()->detach();
                }
                $user->assignRole($role);
            }

            $this->newLine();
            $this->components->info("{$roleLabel} updated successfully!");
            $this->newLine();

            $this->components->twoColumnDetail('Name', $name);
            $this->components->twoColumnDetail('Email', $email);
            $this->components->twoColumnDetail('Role', $role);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->components->error('Failed to update user: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
