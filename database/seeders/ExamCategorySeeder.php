<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the production-style interview and hiring assessment taxonomy.
 *
 * This seeder intentionally replaces all demo-org exam categories so repeated
 * seeds remain deterministic and do not retain obsolete sample categories.
 */
class ExamCategorySeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'demo-org')->first();
        $admin = User::query()->where('email', 'orgadmin@examms.test')->first();

        if (! $organization || ! $admin) {
            $this->command?->warn('ExamCategorySeeder: demo-org or orgadmin missing. Skipping.');

            return;
        }

        ExamCategory::query()
            ->withTrashed()
            ->where('organization_id', $organization->id)
            ->forceDelete();

        $sortOrder = 10;
        foreach ($this->taxonomy() as $root) {
            $this->createNode($root, $organization->id, $admin->id, null, $sortOrder);
            $sortOrder += 10;
        }

        $count = ExamCategory::query()->forOrg($organization->id)->count();
        $this->command?->info("ExamCategorySeeder: {$count} production interview categories seeded.");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function taxonomy(): array
    {
        return [
            $this->category(
                'Primary Interview',
                'The primary hiring assessment used to evaluate role readiness before live interview rounds.',
                'primary-interview',
                [
                    $this->category('Initial Screening', 'Fast pre-interview checks covering aptitude, communication, and baseline role knowledge.', 'primary-initial-screening'),
                    $this->category('Technical Interview', 'Core technical knowledge, applied reasoning, and engineering fundamentals.', 'primary-technical-interview', [
                        $this->category('Backend Engineering', 'PHP, Laravel, APIs, databases, security, and server-side architecture.', 'primary-backend-engineering'),
                        $this->category('Frontend Engineering', 'JavaScript, HTML, CSS, accessibility, browser behavior, and UI engineering.', 'primary-frontend-engineering'),
                        $this->category('Full Stack Engineering', 'End-to-end web application development across frontend and backend.', 'primary-full-stack-engineering'),
                        $this->category('Computer Science Fundamentals', 'Programming, data structures, databases, operating concepts, and complexity.', 'primary-cs-fundamentals'),
                    ]),
                    $this->category('Behavioral Interview', 'Workplace behavior, ownership, communication, collaboration, and STAR responses.', 'primary-behavioral-interview'),
                    $this->category('Managerial Interview', 'Delivery ownership, prioritization, stakeholder management, and team leadership.', 'primary-managerial-interview'),
                    $this->category('Final Panel Interview', 'Cross-functional final-round assessment combining technical and behavioral judgment.', 'primary-final-panel'),
                ]
            ),
            $this->category(
                'Role-Specific Interviews',
                'Specialized assessments aligned to common technology and business roles.',
                'role-specific-interviews',
                [
                    $this->category('PHP Developer', 'Modern PHP, object-oriented design, testing, security, and backend fundamentals.', 'role-php-developer'),
                    $this->category('Laravel Developer', 'Laravel routing, Eloquent, validation, queues, caching, testing, and architecture.', 'role-laravel-developer'),
                    $this->category('JavaScript Developer', 'JavaScript language features, asynchronous code, DOM, and browser APIs.', 'role-javascript-developer'),
                    $this->category('Database Developer', 'SQL, normalization, transactions, indexing, query design, and data integrity.', 'role-database-developer'),
                    $this->category('Frontend Developer', 'Semantic HTML, responsive CSS, accessibility, and JavaScript UI fundamentals.', 'role-frontend-developer'),
                    $this->category('Software Engineer', 'Programming fundamentals, algorithms, data structures, and engineering practices.', 'role-software-engineer'),
                    $this->category('Technical Support Engineer', 'Troubleshooting, communication, incident handling, and technical fundamentals.', 'role-technical-support'),
                ]
            ),
            $this->category(
                'Campus and Graduate Hiring',
                'Structured assessments for internships, graduate programs, and campus recruitment.',
                'campus-graduate-hiring',
                [
                    $this->category('BCA Graduate Interview', 'Computer applications, programming, web, database, and aptitude screening.', 'campus-bca-graduate'),
                    $this->category('MCA Graduate Interview', 'Advanced computer applications, software engineering, database, and coding readiness.', 'campus-mca-graduate'),
                    $this->category('Internship Screening', 'Foundational aptitude, programming, communication, and learning potential.', 'campus-internship-screening'),
                    $this->category('Graduate Aptitude Round', 'Quantitative aptitude, reasoning, general awareness, and problem solving.', 'campus-graduate-aptitude'),
                    $this->category('Campus Technical Round', 'Objective computer science and programming assessment before interviews.', 'campus-technical-round'),
                ]
            ),
            $this->category(
                'Specialized Assessment Rounds',
                'Focused rounds used alongside the primary interview workflow.',
                'specialized-assessment-rounds',
                [
                    $this->category('Coding Fundamentals Round', 'Programming logic, clean code, complexity, and implementation reasoning.', 'round-coding-fundamentals'),
                    $this->category('Data Structures Round', 'Arrays, linked structures, stacks, queues, trees, graphs, and hashing.', 'round-data-structures'),
                    $this->category('SQL and Database Round', 'SQL proficiency, database design, transactions, and query optimization.', 'round-sql-database'),
                    $this->category('Web Fundamentals Round', 'HTML, CSS, JavaScript, accessibility, and responsive web concepts.', 'round-web-fundamentals'),
                    $this->category('Aptitude and Reasoning Round', 'Numerical ability, analytical reasoning, and time-bound problem solving.', 'round-aptitude-reasoning'),
                    $this->category('General Awareness Round', 'Technology awareness, business context, and general knowledge.', 'round-general-awareness'),
                    $this->category('Communication and HR Round', 'Interview readiness, professional communication, and workplace judgment.', 'round-communication-hr'),
                ]
            ),
            $this->category(
                'Proctored and Compliance Assessments',
                'Controlled assessments for high-stakes hiring and verified skill evaluation.',
                'proctored-compliance-assessments',
                [
                    $this->category('Remote Proctored Interview', 'Strict browser, webcam, identity, and integrity-controlled assessment.', 'proctored-remote-interview'),
                    $this->category('Certification-Style Evaluation', 'Timed, scored, and repeat-limited professional skill validation.', 'proctored-certification'),
                    $this->category('Internal Skill Verification', 'Employee capability checks for projects, promotions, and role transitions.', 'proctored-internal-verification'),
                ]
            ),
            $this->category(
                'Practice and Readiness',
                'Low-stakes practice workflows for candidates preparing for interview assessments.',
                'practice-readiness',
                [
                    $this->category('Interview Readiness Practice', 'Behavioral, resume, HR, and interview strategy practice.', 'practice-interview-readiness'),
                    $this->category('Technical Mock Interview', 'Mixed technical mock assessment with reusable attempts.', 'practice-technical-mock'),
                    $this->category('Aptitude Practice', 'Untimed or repeatable quantitative and reasoning preparation.', 'practice-aptitude'),
                ]
            ),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private function category(string $name, string $description, string $slug, array $children = []): array
    {
        return compact('name', 'description', 'slug', 'children');
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function createNode(
        array $node,
        int $organizationId,
        int $adminId,
        ?int $parentId,
        int $sortOrder
    ): void {
        $children = $node['children'] ?? [];

        $category = ExamCategory::query()->create([
            'organization_id' => $organizationId,
            'parent_id' => $parentId,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'name' => $node['name'],
            'slug' => $node['slug'],
            'description' => $node['description'],
            'status' => 'active',
            'sort_order' => $sortOrder,
            'meta_title' => Str::limit($node['name'].' Interview Assessments', 255, ''),
            'meta_description' => Str::limit($node['description'], 500, ''),
            'meta_keywords' => implode(', ', array_unique([
                Str::lower($node['name']),
                'interview assessment',
                'candidate evaluation',
                'hiring exam',
            ])),
            'canonical_url' => rtrim((string) config('app.url'), '/').'/exam-categories/'.$node['slug'],
            'og_title' => $node['name'].' Assessments',
            'og_description' => Str::limit($node['description'], 500, ''),
            'robots' => 'index,follow',
            'schema_markup' => json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $node['name'].' Interview Assessments',
                'description' => $node['description'],
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ai_generated' => false,
            'ai_improve' => false,
        ]);

        foreach (array_values($children) as $index => $child) {
            $this->createNode(
                $child,
                $organizationId,
                $adminId,
                $category->id,
                ($index + 1) * 10
            );
        }
    }
}
