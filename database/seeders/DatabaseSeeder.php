<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            ProfileSeeder::class,
            OrganizationSeeder::class,
            UserOrganizationSeeder::class,
            CategorySeeder::class,
            QuestionSeeder::class,
            ExamSeeder::class,
        ]);

        $this->command->info('Demo logins (password: password):');
        $this->command->table(
            ['Email', 'Panel'],
            [
                ['admin@examms.test', 'Super admin /admin'],
                ['orgadmin@examms.test', 'Org admin /org-admin'],
                ['editor@examms.test', 'Editor /editor'],
                ['viewer@examms.test', 'Candidate /viewer'],
            ]
        );
    }
}
