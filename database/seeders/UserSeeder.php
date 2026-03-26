<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin', 'email' => 'admin@examms.test'],
            ['name' => 'Org Admin User', 'email' => 'orgadmin@examms.test'],
            ['name' => 'Editor User', 'email' => 'editor@examms.test'],
            ['name' => 'Candidate User', 'email' => 'viewer@examms.test'],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                ]
            );
        }

        User::where('email', 'admin@examms.test')->first()?->syncRoles(['admin']);
    }
}
