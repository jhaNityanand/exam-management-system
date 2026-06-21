<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProfileSeeder::class,
            OrganizationSeeder::class,
            UserOrganizationSeeder::class,
            QuestionCategorySeeder::class,
            QuestionSeeder::class,
            ExamSeeder::class,
        ]);

        $this->command->info('Demo logins (password: password):');
        $this->command->table(
            ['Email', 'Role', 'Access'],
            [
                ['admin@examms.test',    'Admin',     '/admin — super admin panel'],
                ['orgadmin@examms.test', 'Org Admin', '/admin — org-level management'],
                ['editor@examms.test',   'Editor',    '/admin — question & exam creation'],
                ['student@examms.test',  'Viewer',    '/admin — exam taking & results'],
            ]
        );
    }
}
