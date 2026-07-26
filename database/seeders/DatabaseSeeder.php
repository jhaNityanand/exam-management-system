<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Wipe previous uploads, then rebuild demo data and regenerate gallery images under storage/app/public.
        $this->call([
            ClearUploadedMediaSeeder::class,
            UserSeeder::class,
            ProfileSeeder::class,
            OrganizationSeeder::class,
            UserOrganizationSeeder::class,
            QuestionCategorySeeder::class,
            QuestionSeeder::class,
            ExamCategorySeeder::class,
            ExamInstructionRuleSeeder::class,
            ExamCandidateInstructionTemplateSeeder::class,
            ExamSeeder::class,
            BlogCategorySeeder::class,
            BlogTagSeeder::class,
            BlogSeeder::class,
            NewsSeeder::class,
            FrontendCmsSeeder::class,
        ]);

        $this->command->info('Demo logins (password: password):');
        $this->command->table(
            ['Email', 'Role', 'Access'],
            [
                ['admin@example.in', 'Application Admin', '/admin — full admin panel'],
                ['admin@examtube.in', 'Org Admin', '/admin — organization management'],
                ['candidate@examtube.in', 'Candidate', '/account — exams & results'],
            ]
        );
    }
}
