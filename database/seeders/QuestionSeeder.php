<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->first();
        $cat = Category::where('organization_id', $org?->id)->where('name', 'General')->first();
        $editor = User::where('email', 'editor@examms.test')->first();

        if (! $org || ! $cat || ! $editor) {
            return;
        }

        Question::firstOrCreate(
            [
                'organization_id' => $org->id,
                'body' => 'What is 2 + 2?',
            ],
            [
                'category_id' => $cat->id,
                'created_by' => $editor->id,
                'status' => 'active',
                'type' => 'mcq',
                'allows_multiple' => false,
                'options' => [
                    ['text' => '3', 'image_path' => null],
                    ['text' => '4', 'image_path' => null],
                    ['text' => '5', 'image_path' => null],
                ],
                'correct_answer' => '4',
                'correct_answers' => null,
                'marks' => 1,
                'difficulty' => 'easy',
            ]
        );
    }
}
