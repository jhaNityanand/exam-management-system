<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->first();
        if (! $org) {
            return;
        }

        $map = [
            'orgadmin@examms.test' => 'org_admin',
            'editor@examms.test' => 'editor',
            'viewer@examms.test' => 'viewer',
        ];

        foreach ($map as $email => $role) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $org->users()->syncWithoutDetaching([
                    $user->id => ['role' => $role, 'status' => 'active'],
                ]);
            }
        }
    }
}
