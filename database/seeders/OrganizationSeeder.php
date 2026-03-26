<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@examms.test')->first();

        Organization::firstOrCreate(
            ['slug' => 'demo-org'],
            [
                'name' => 'Demo Organization',
                'description' => 'Default organization for development and testing.',
                'status' => 'active',
                'user_id' => $admin?->id,
            ]
        );
    }
}
