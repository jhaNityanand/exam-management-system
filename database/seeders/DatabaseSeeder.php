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
            LlmAccountSeeder::class,
        ]);

        $this->command->info('Seeded accounts (password: password):');
        $this->command->table(
            ['Email', 'Role', 'Access'],
            [
                [\Database\Seeders\Support\SeederContact::EMAIL_ADMIN, 'admin', '/admin — application admin'],
                [\Database\Seeders\Support\SeederContact::EMAIL_INFO, 'org_admin', '/admin — organization admin'],
                ['candidate@examtube.in', 'candidate', '/account — exams & results'],
            ]
        );
    }
}
