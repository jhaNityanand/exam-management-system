<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super admin
        User::firstOrCreate(
            ['email' => 'admin@examms.test'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );

        // Org admin
        User::firstOrCreate(
            ['email' => 'orgadmin@examms.test'],
            [
                'name'     => 'Org Admin User',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );

        // Editor
        User::firstOrCreate(
            ['email' => 'editor@examms.test'],
            [
                'name'     => 'Editor User',
                'password' => Hash::make('password'),
                'status'   => 'active',
            ]
        );

        // Viewer / Candidate
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
