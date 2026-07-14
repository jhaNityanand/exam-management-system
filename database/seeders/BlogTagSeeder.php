<?php

namespace Database\Seeders;

use App\Models\BlogTag;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds organization-scoped blog tags for demo-org.
 */
class BlogTagSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $tags = [
        'Laravel',
        'Eloquent',
        'PHP 8',
        'Node.js',
        'Express',
        'React',
        'Vue.js',
        'REST API',
        'GraphQL',
        'MySQL',
        'PostgreSQL',
        'Docker',
        'CI/CD',
        'OWASP',
        'Machine Learning',
        'Prompt Engineering',
        'Soft Skills',
        'System Design',
        'Testing',
        'Performance',
    ];

    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->firstOrFail();

        foreach ($this->tags as $name) {
            BlogTag::firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'slug'            => Str::slug($name),
                ],
                [
                    'organization_id' => $org->id,
                    'name'            => $name,
                ]
            );
        }

        $count = BlogTag::forOrg($org->id)->count();
        $this->command->info("BlogTagSeeder: seeded {$count} blog tags.");
    }
}
