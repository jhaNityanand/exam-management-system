<?php

namespace Database\Seeders;

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('slug', 'demo-org')->first() ?? Organization::query()->first();
        $author = User::query()->where('email', 'editor@examms.test')->first()
            ?? User::query()->first();

        if (! $org || ! $author) {
            $this->command?->warn('NewsSeeder: organization or author missing. Skipping.');

            return;
        }

        $campus = NewsCategory::query()->updateOrCreate(
            ['organization_id' => $org->id, 'slug' => 'campus-alerts'],
            [
                'name' => 'Campus Alerts',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $author->id,
            ]
        );

        $careers = NewsCategory::query()->updateOrCreate(
            ['organization_id' => $org->id, 'slug' => 'career-updates'],
            [
                'name' => 'Career Updates',
                'status' => 'active',
                'sort_order' => 2,
                'created_by' => $author->id,
            ]
        );

        $items = [
            [
                'title' => 'Summer mock test window opens for SSC aspirants',
                'category_id' => $campus->id,
                'short_description' => 'Timed practice papers covering quantitative aptitude and reasoning go live this week.',
                'excerpt' => 'Examtube has released a new summer series of free mock tests tuned for SSC Tier-I aspirants.',
                'content' => '<p>Candidates can attempt daily mocks with exam-day timers, instant scoring, and negative marking that mirrors the official pattern.</p><p>Register on Examtube.in and choose the SSC practice category to get started.</p>',
                'is_breaking' => true,
                'is_featured' => true,
                'is_trending' => true,
            ],
            [
                'title' => 'Banking recruitment calendars updated for major exams',
                'category_id' => $careers->id,
                'short_description' => 'Stay ahead with the latest probationary officer and clerk exam timelines.',
                'excerpt' => 'A roundup of confirmed banking exam dates and how to structure your 60-day prep plan.',
                'content' => '<p>Institute mentors recommend focusing on sectional mocks early, then full-length papers in the final month.</p>',
                'is_breaking' => false,
                'is_featured' => true,
                'is_trending' => true,
            ],
            [
                'title' => 'GATE practice labs expand with cloud computing tracks',
                'category_id' => $campus->id,
                'short_description' => 'New question banks cover distributed systems and DevOps fundamentals.',
                'excerpt' => 'Engineering students preparing for GATE can now practice cloud-focused sets alongside traditional CS papers.',
                'content' => '<p>Each set includes detailed explanations and topic tags so you can revisit weak areas quickly.</p>',
                'is_breaking' => false,
                'is_featured' => false,
                'is_trending' => false,
            ],
        ];

        foreach ($items as $item) {
            News::query()->updateOrCreate(
                [
                    'organization_id' => $org->id,
                    'slug' => Str::slug($item['title']),
                ],
                [
                    'news_category_id' => $item['category_id'],
                    'title' => $item['title'],
                    'short_description' => $item['short_description'],
                    'excerpt' => $item['excerpt'],
                    'content' => $item['content'],
                    'author_id' => $author->id,
                    'author_name' => $author->name,
                    'status' => News::STATUS_PUBLISHED,
                    'visibility' => News::VISIBILITY_PUBLIC,
                    'is_featured' => $item['is_featured'],
                    'is_breaking' => $item['is_breaking'],
                    'is_trending' => $item['is_trending'],
                    'published_at' => now()->subDays(rand(1, 10)),
                    'created_by' => $author->id,
                ]
            );
        }
    }
}
