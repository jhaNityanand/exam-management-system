<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin user
        User::firstOrCreate(
            ['email' => 'admin@examms.test'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );

        // Default student user
        User::firstOrCreate(
            ['email' => 'student@examms.test'],
            [
                'name'     => 'Student User',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );
    }
}
