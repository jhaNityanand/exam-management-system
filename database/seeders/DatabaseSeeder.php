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
            OrganizationSeeder::class,
            UserOrganizationSeeder::class,
            ProfileSeeder::class,
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
            DemoMediaSeeder::class,
            DemoEngagementSeeder::class,
            ExamAttemptSeeder::class,
        ]);

        $this->command->info('Demo logins (password: password):');
        $this->command->table(
            ['Email', 'Role', 'Access'],
            [
                [\Database\Seeders\Support\SeederContact::EMAIL_ADMIN, 'Application Admin', '/admin — full admin panel'],
                [\Database\Seeders\Support\SeederContact::EMAIL_INFO, 'Org Admin', '/admin — organization management'],
                ['candidate@examtube.in', 'Candidate', '/account — exams & results'],
            ]
        );
    }
}
