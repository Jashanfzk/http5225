<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure administrator role exists
        $adminRole = Role::firstOrCreate(['name' => 'administrator']);

        // Create admin user if it doesn't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@myplaceinthisworld.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'), // Change this password after first login
                'is_owner' => false,
                'school_id' => null, // Admin doesn't need a school
            ]
        );

        // Assign administrator role if not already assigned
        if (!$admin->hasRole('administrator')) {
            $admin->assignRole('administrator');
        }

        $this->command->info('Administrator account created:');
        $this->command->info('Email: admin@myplaceinthisworld.com');
        $this->command->info('Password: password');
        $this->command->warn('Please change the password after first login!');
    }
}

