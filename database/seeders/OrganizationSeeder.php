<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\Support\SeederContact;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', SeederContact::EMAIL_ADMIN)->first();

        Organization::query()->updateOrCreate(
            ['slug' => 'demo-org'],
            [
                'name' => 'Examtube',
                'description' => 'Primary organization workspace for Examtube.',
                'status' => 'active',
                'user_id' => $admin?->id,
                'ai_generated' => false,
                'ai_improve' => false,
                'is_ai_generated' => false,
                'is_sitemap_url_created' => false,
            ]
        );
    }
}
