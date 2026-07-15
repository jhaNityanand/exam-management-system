<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\Organization;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $org   = Organization::where('slug', 'demo-org')->first();
        $admin = User::where('email', 'orgadmin@examms.test')->first();

        if (! $org || ! $admin) {
            $this->command->warn('ExamSeeder: demo-org or orgadmin not found. Skipping.');
            return;
        }

        $orgId     = $org->id;
        $questions = Question::where('organization_id', $orgId)->limit(20)->get();

        // Resolve category IDs (created by ExamCategorySeeder)
        $aptitudeCat  = ExamCategory::where('organization_id', $orgId)->where('slug', 'corporate-aptitude')->first();
        $cloudCat     = ExamCategory::where('organization_id', $orgId)->where('slug', 'cert-cloud-devops')->first();
        $scienceCat   = ExamCategory::where('organization_id', $orgId)->where('slug', 'university-entrance-science')->first();

        $exams = [
            [
                'title'             => 'Demo Placement Test',
                'description'       => '<p>A sample placement exam seeded for development and UI preview purposes.</p>',
                'status'            => 'draft',
                'exam_mode'         => 'standard',
                'exam_format'       => ['mcq'],
                'visibility'        => 'private',
                'duration'          => 30,
                'pass_percentage'   => 50,
                'max_attempts'      => 2,
                'total_questions'   => 10,
                'total_marks'       => 20,
                'passing_marks'     => 10,
                'paper_sets'        => 1,
                'shuffle_questions' => false,
                'shuffle_options'   => false,
                'category_id'       => $aptitudeCat?->id,
            ],
            [
                'title'             => 'Cloud & DevOps Certification Mock',
                'description'       => '<p>A timed mock exam covering AWS, GCP, Docker, and CI/CD practices for cloud engineers.</p>',
                'status'            => 'published',
                'exam_mode'         => 'proctored',
                'exam_format'       => ['mcq', 'multi_select'],
                'visibility'        => 'public',
                'difficulty_level'  => 'advanced',
                'duration'          => 90,
                'pass_percentage'   => 70,
                'max_attempts'      => 3,
                'total_questions'   => 50,
                'total_marks'       => 100,
                'passing_marks'     => 70,
                'paper_sets'        => 2,
                'enable_exam_timer' => true,
                'auto_submit_on_timer_end' => true,
                'shuffle_questions' => true,
                'shuffle_options'   => true,
                'enable_negative_marking' => true,
                'negative_mark_per_question' => 0.25,
                'category_id'       => $cloudCat?->id,
            ],
            [
                'title'             => 'University Science Entrance — Practice Run',
                'description'       => '<p>Practice exam for students preparing for university science entrance examinations.</p>',
                'status'            => 'active',
                'exam_mode'         => 'practice',
                'exam_format'       => ['mcq', 'written'],
                'visibility'        => 'invite_only',
                'difficulty_level'  => 'intermediate',
                'duration'          => 120,
                'pass_percentage'   => 60,
                'max_attempts'      => 5,
                'total_questions'   => 75,
                'total_marks'       => 150,
                'passing_marks'     => 90,
                'paper_sets'        => 1,
                'shuffle_questions' => true,
                'shuffle_options'   => false,
                'category_id'       => $scienceCat?->id,
            ],
        ];

        foreach ($exams as $data) {
            $exam = Exam::firstOrCreate(
                [
                    'organization_id' => $orgId,
                    'title'           => $data['title'],
                ],
                array_merge($data, [
                    'organization_id' => $orgId,
                    'created_by'      => $admin->id,
                    'slug'            => Str::slug($data['title']),
                ])
            );

            if (blank($exam->slug)) {
                $exam->forceFill(['slug' => Str::slug($exam->title)])->save();
            }

            // Attach first few questions if available
            if ($questions->isNotEmpty()) {
                $take  = min(5, $questions->count());
                $slice = $questions->take($take);
                $sync  = [];
                foreach ($slice->values() as $i => $q) {
                    $sync[$q->id] = ['sort_order' => $i, 'status' => 'active'];
                }
                $exam->questions()->sync($sync);
            }
        }

        $this->command->info('ExamSeeder: seeded ' . Exam::where('organization_id', $orgId)->count() . ' exams.');
    }
}
