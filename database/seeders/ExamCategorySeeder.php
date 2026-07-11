<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * ExamCategorySeeder
 *
 * Seeds a realistic multi-level exam category tree.
 *
 * Tree structure:
 *   Academic Examinations
 *     ├── University Entrance
 *     │     ├── Science Stream
 *     │     └── Commerce Stream
 *     └── School Level
 *           ├── Primary School
 *           └── Secondary School
 *   Professional Certifications
 *     ├── Information Technology
 *     │     ├── Cloud & DevOps
 *     │     └── Web Development
 *     └── Finance & Accounting
 *   Corporate Hiring
 *     ├── Aptitude & Reasoning
 *     └── Technical Screening
 */
class ExamCategorySeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->first();

        if (! $org) {
            $this->command->warn('ExamCategorySeeder: demo-org not found. Skipping.');
            return;
        }

        $orgId = $org->id;

        $tree = [
            [
                'name'             => 'Academic Examinations',
                'description'      => 'Covers entrance and standardised examinations for schools, colleges, and universities.',
                'status'           => 'active',
                'slug'             => 'academic-examinations',
                'meta_title'       => 'Academic Examinations — Exam Categories',
                'meta_description' => 'Browse academic exam categories including university entrance and school-level tests.',
                'meta_keywords'    => 'academic, university, school, entrance, examinations',
                'og_title'         => 'Academic Examination Categories',
                'og_description'   => 'Explore exam categories for academic institutions.',
                'children' => [
                    [
                        'name'        => 'University Entrance',
                        'description' => 'Entrance examinations for undergraduate and postgraduate programmes.',
                        'status'      => 'active',
                        'slug'        => 'university-entrance',
                        'children'    => [
                            [
                                'name'        => 'Science Stream',
                                'description' => 'Physics, Chemistry, Biology, and Mathematics for science-track applicants.',
                                'status'      => 'active',
                                'slug'        => 'university-entrance-science',
                            ],
                            [
                                'name'        => 'Commerce Stream',
                                'description' => 'Accounting, Economics, and Business Studies for commerce applicants.',
                                'status'      => 'active',
                                'slug'        => 'university-entrance-commerce',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'School Level',
                        'description' => 'Standardised assessments designed for primary and secondary school students.',
                        'status'      => 'active',
                        'slug'        => 'school-level',
                        'children'    => [
                            [
                                'name'        => 'Primary School',
                                'description' => 'Assessments for students in grades 1–6.',
                                'status'      => 'active',
                                'slug'        => 'school-primary',
                            ],
                            [
                                'name'        => 'Secondary School',
                                'description' => 'Assessments for students in grades 7–12.',
                                'status'      => 'active',
                                'slug'        => 'school-secondary',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name'             => 'Professional Certifications',
                'description'      => 'Industry-recognised certification exams across technology, finance, and other professional domains.',
                'status'           => 'active',
                'slug'             => 'professional-certifications',
                'meta_title'       => 'Professional Certifications — Exam Categories',
                'meta_description' => 'Explore certification exam categories for IT, finance, and professional development.',
                'meta_keywords'    => 'certification, professional, IT, cloud, finance, accounting',
                'og_title'         => 'Professional Certification Categories',
                'og_description'   => 'Certification exams across multiple professional fields.',
                'children' => [
                    [
                        'name'        => 'Information Technology',
                        'description' => 'Certifications in software development, cloud infrastructure, networking, and security.',
                        'status'      => 'active',
                        'slug'        => 'certification-it',
                        'children'    => [
                            [
                                'name'        => 'Cloud & DevOps',
                                'description' => 'AWS, GCP, Azure, Kubernetes, Docker, and CI/CD pipelines.',
                                'status'      => 'active',
                                'slug'        => 'cert-cloud-devops',
                            ],
                            [
                                'name'        => 'Web Development',
                                'description' => 'Frontend, backend, and full-stack development frameworks and best practices.',
                                'status'      => 'active',
                                'slug'        => 'cert-web-development',
                            ],
                        ],
                    ],
                    [
                        'name'        => 'Finance & Accounting',
                        'description' => 'CPA, CFA, ACCA, and other financial certification preparation exams.',
                        'status'      => 'active',
                        'slug'        => 'certification-finance',
                    ],
                ],
            ],
            [
                'name'             => 'Corporate Hiring',
                'description'      => 'Pre-employment screening tests used by organisations during candidate selection.',
                'status'           => 'active',
                'slug'             => 'corporate-hiring',
                'meta_title'       => 'Corporate Hiring — Exam Categories',
                'meta_description' => 'Recruitment and aptitude exam categories for corporate hiring processes.',
                'meta_keywords'    => 'hiring, recruitment, aptitude, screening, corporate',
                'og_title'         => 'Corporate Hiring Exam Categories',
                'og_description'   => 'Streamline candidate screening with categorised corporate hiring exams.',
                'children' => [
                    [
                        'name'        => 'Aptitude & Reasoning',
                        'description' => 'Logical reasoning, verbal ability, numerical aptitude, and problem-solving.',
                        'status'      => 'active',
                        'slug'        => 'corporate-aptitude',
                    ],
                    [
                        'name'        => 'Technical Screening',
                        'description' => 'Domain-specific technical tests for engineering, development, and data roles.',
                        'status'      => 'active',
                        'slug'        => 'corporate-technical',
                    ],
                ],
            ],
        ];

        foreach ($tree as $rootData) {
            $this->seedNode($rootData, null, $orgId);
        }

        $this->command->info('ExamCategorySeeder: seeded ' . ExamCategory::count() . ' exam categories.');
    }

    protected function seedNode(array $data, ?int $parentId, int $orgId): ExamCategory
    {
        $children = $data['children'] ?? [];
        unset($data['children']);

        $category = ExamCategory::firstOrCreate(
            [
                'organization_id' => $orgId,
                'parent_id'       => $parentId,
                'name'            => $data['name'],
            ],
            array_merge($data, ['organization_id' => $orgId, 'parent_id' => $parentId])
        );

        foreach ($children as $childData) {
            $this->seedNode($childData, $category->id, $orgId);
        }

        return $category;
    }
}
