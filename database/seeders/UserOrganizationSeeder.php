<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationRoles;
use Illuminate\Database\Seeder;

class UserOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('slug', 'demo-org')->first()
            ?: Organization::query()->where('slug', 'examtube')->first()
            ?: Organization::query()->first();

        if (! $org) {
            return;
        }

        $assignments = [
            'admin@example.in' => OrganizationRoles::ADMIN,
            'admin@examtube.in' => OrganizationRoles::ORG_ADMIN,
            'candidate@examtube.in' => OrganizationRoles::CANDIDATE,
        ];

        foreach ($assignments as $email => $role) {
            $user = User::query()->where('email', $email)->first();
            if ($user) {
                $org->users()->syncWithoutDetaching([
                    $user->id => ['role' => $role, 'status' => 'active'],
                ]);
            }
        }
    }
}
