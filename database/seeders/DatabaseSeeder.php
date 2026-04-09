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
            CategorySeeder::class,
            QuestionSeeder::class,
            ExamSeeder::class,
        ]);

        $this->command->info('Demo logins (password: password):');
        $this->command->table(
            ['Email', 'Access'],
            [
                ['admin@examms.test', 'Admin panel  /admin'],
                ['student@examms.test', 'Student / take exams'],
            ]
        );
    }
}
