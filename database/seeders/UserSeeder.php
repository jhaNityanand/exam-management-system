<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\UniqueUserSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@example.in',
                'name' => 'Application Admin',
                'username' => 'app-admin',
            ],
            [
                'email' => 'admin@examtube.in',
                'name' => 'Organization Admin',
                'username' => 'org-admin',
            ],
            [
                'email' => 'candidate@examtube.in',
                'name' => 'Candidate User',
                'username' => 'candidate',
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => Hash::make('password'),
                    'status' => 'active',
                ]
            );

            UniqueUserSlug::ensureFor($user);
        }
    }
}
