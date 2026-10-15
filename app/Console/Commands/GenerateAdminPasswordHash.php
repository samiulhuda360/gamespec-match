<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GenerateAdminPasswordHash extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:generate-password
                            {password? : The password to hash (will prompt if not provided)}';

    /**
     * The console command description.
     */
    protected $description = 'Generate a bcrypt hash for the admin password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $password = $this->argument('password');

        // If no password provided, prompt securely
        if (empty($password)) {
            $password = $this->secret('Enter the admin password (min 8 characters)');

            if (strlen($password) < 8) {
                $this->error('Password must be at least 8 characters long.');
                return self::FAILURE;
            }

            $confirm = $this->secret('Confirm the password');

            if ($password !== $confirm) {
                $this->error('Passwords do not match.');
                return self::FAILURE;
            }
        }

        // Generate bcrypt hash
        $hash = Hash::make($password);

        $this->newLine();
        $this->info('✅ Admin password hash generated successfully!');
        $this->newLine();
        $this->line('Add this to your .env file:');
        $this->newLine();
        $this->line("ADMIN_PASSWORD_HASH=\"{$hash}\"");
        $this->newLine();
        $this->warn('⚠️  Keep this hash secure and never commit it to version control!');
        $this->newLine();

        return self::SUCCESS;
    }
}
