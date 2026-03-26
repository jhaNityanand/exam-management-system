<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Organization;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->first();
        $admin = User::where('email', 'orgadmin@examms.test')->first();
        $q = Question::where('organization_id', $org?->id)->first();

        if (! $org || ! $admin || ! $q) {
            return;
        }

        $exam = Exam::firstOrCreate(
            [
                'organization_id' => $org->id,
                'title' => 'Demo placement test',
            ],
            [
                'created_by' => $admin->id,
                'description' => 'Sample exam seeded for development.',
                'duration' => 30,
                'pass_percentage' => 50,
                'max_attempts' => 2,
                'status' => 'draft',
                'exam_mode' => 'standard',
            ]
        );

        $exam->questions()->sync([
            $q->id => ['sort_order' => 0, 'status' => 'active'],
        ]);
    }
}
