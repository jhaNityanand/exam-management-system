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

        // Add the student user as a member of the demo organization
        $student = User::where('email', 'student@examms.test')->first();
        if ($student) {
            $org->users()->syncWithoutDetaching([
                $student->id => ['status' => 'active'],
            ]);
        }
    }
}
