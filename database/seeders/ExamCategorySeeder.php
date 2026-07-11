<?php

namespace Database\Seeders;

use App\Models\ExamCategory;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a production-like multi-level exam category hierarchy for demo-org.
 *
 * Preserves slugs used by ExamSeeder:
 *   - university-entrance-science
 *   - cert-cloud-devops
 *   - corporate-aptitude
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

        $this->insertTree($this->tree(), $org->id, null, '');

        $count = ExamCategory::forOrg($org->id)->count();
        $this->command->info("ExamCategorySeeder: seeded {$count} exam categories.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tree(): array
    {
        return [
            $this->cat('Academic Examinations', 'Entrance and standardised exams for schools, colleges, and universities.', [
                $this->cat('University Entrance', 'Undergraduate and postgraduate entrance examinations.', [
                    $this->cat('Science Stream', 'Physics, Chemistry, Biology, and Mathematics for science-track applicants.', [
                        $this->cat('Engineering Entrance', 'JEE-style physics, chemistry, and mathematics screening.'),
                        $this->cat('Medical Entrance', 'NEET-style biology, chemistry, and physics screening.'),
                        $this->cat('Pure Sciences', 'B.Sc. / integrated science programme entrance tests.'),
                    ], 'university-entrance-science'),
                    $this->cat('Commerce Stream', 'Accounting, Economics, and Business Studies for commerce applicants.', [
                        $this->cat('B.Com Entrance', 'Commerce undergraduate entrance assessments.'),
                        $this->cat('BBA Entrance', 'Business administration aptitude and general awareness.'),
                    ]),
                    $this->cat('Arts and Humanities', 'History, literature, languages, and social science entrances.', [
                        $this->cat('Liberal Arts', 'Interdisciplinary arts and humanities screening.'),
                        $this->cat('Law Entrance', 'CLAT-style legal reasoning and current affairs.'),
                    ]),
                    $this->cat('Postgraduate Entrance', 'Masters and professional postgraduate programme tests.', [
                        $this->cat('MBA Entrance', 'CAT/GMAT-style quantitative, verbal, and logical sections.'),
                        $this->cat('M.Tech Entrance', 'GATE-style engineering postgraduate screening.'),
                    ]),
                ], 'university-entrance'),
                $this->cat('School Level', 'Standardised assessments for primary and secondary students.', [
                    $this->cat('Primary School', 'Assessments for students in grades 1–5.', [
                        $this->cat('Foundation Literacy', 'Reading, writing, and early language skills.'),
                        $this->cat('Foundation Numeracy', 'Number sense, arithmetic, and problem solving.'),
                    ], 'school-primary'),
                    $this->cat('Middle School', 'Assessments for students in grades 6–8.', [
                        $this->cat('Science Olympiad Prep', 'Concept checks across physics, chemistry, and biology.'),
                        $this->cat('Math Olympiad Prep', 'Algebra, geometry, and number theory foundations.'),
                    ]),
                    $this->cat('Secondary School', 'Assessments for students in grades 9–12.', [
                        $this->cat('Board Exam Practice', 'Subject-wise board pattern mock examinations.'),
                        $this->cat('Competitive Foundation', 'Early aptitude and reasoning for entrance prep.'),
                    ], 'school-secondary'),
                ], 'school-level'),
                $this->cat('Scholarship Exams', 'Merit-based scholarship and talent search examinations.', [
                    $this->cat('National Scholarship', 'Nationwide talent search and scholarship screening.'),
                    $this->cat('State Scholarship', 'State-level merit and means scholarship tests.'),
                ]),
            ], 'academic-examinations'),

            $this->cat('Competitive Government Exams', 'Public service and government recruitment examinations.', [
                $this->cat('Civil Services', 'UPSC and state PSC style preparatory assessments.', [
                    $this->cat('Prelims', 'Objective general studies and aptitude screening.'),
                    $this->cat('Mains Practice', 'Descriptive general studies and essay practice.'),
                    $this->cat('Interview Prep', 'Personality test and current affairs viva practice.'),
                ]),
                $this->cat('Banking Exams', 'IBPS, SBI, and related banking recruitment tests.', [
                    $this->cat('Quantitative Aptitude', 'Arithmetic, DI, and numerical ability.'),
                    $this->cat('Reasoning Ability', 'Puzzles, seating, and logical reasoning.'),
                    $this->cat('English Language', 'Grammar, comprehension, and verbal ability.'),
                ]),
                $this->cat('Defence Exams', 'NDA, CDS, and defence academy screening.', [
                    $this->cat('NDA Written', 'Mathematics and general ability paper practice.'),
                    $this->cat('CDS Written', 'English, GK, and elementary mathematics.'),
                ]),
                $this->cat('Teaching Eligibility', 'TET, CTET, and related teaching eligibility tests.', [
                    $this->cat('Child Development', 'Pedagogy and child psychology fundamentals.'),
                    $this->cat('Subject Pedagogy', 'Language, mathematics, and EVS teaching methods.'),
                ]),
                $this->cat('Railway and SSC', 'Staff Selection and railway recruitment practice.', [
                    $this->cat('General Awareness', 'Static GK and current affairs for SSC/RRB.'),
                    $this->cat('Technical Posts', 'Engineering and trade-specific technical papers.'),
                ]),
            ]),

            $this->cat('Professional Certifications', 'Industry-recognised certification exams across technology and business.', [
                $this->cat('Information Technology', 'Software, cloud, networking, and security certifications.', [
                    $this->cat('Cloud & DevOps', 'AWS, GCP, Azure, Kubernetes, Docker, and CI/CD pipelines.', [
                        $this->cat('AWS Associate', 'Solutions Architect and Developer associate tracks.'),
                        $this->cat('Kubernetes Admin', 'CKA-style cluster administration practice.'),
                    ], 'cert-cloud-devops'),
                    $this->cat('Web Development', 'Frontend, backend, and full-stack frameworks and practices.', [
                        $this->cat('Frontend Frameworks', 'React, Vue, and Angular certification-style drills.'),
                        $this->cat('Backend Frameworks', 'Laravel, Node.js, and API design assessments.'),
                    ], 'cert-web-development'),
                    $this->cat('Cybersecurity Certs', 'Security+, CEH, and application security practice.', [
                        $this->cat('Network Security', 'Firewalls, VPN, and secure protocols.'),
                        $this->cat('Secure Coding', 'OWASP Top 10 and threat modeling.'),
                    ]),
                    $this->cat('Data and Analytics', 'SQL, BI, and data engineering certification prep.', [
                        $this->cat('SQL Proficiency', 'Query writing, tuning, and schema design.'),
                        $this->cat('Data Visualization', 'Dashboard design and analytical storytelling.'),
                    ]),
                ], 'certification-it'),
                $this->cat('Finance & Accounting', 'CPA, CFA, ACCA, and financial certification preparation.', [
                    $this->cat('Financial Reporting', 'IFRS/GAAP reporting and analysis practice.'),
                    $this->cat('Investment Analysis', 'Equity, fixed income, and portfolio concepts.'),
                    $this->cat('Taxation Practice', 'Direct and indirect tax concept checks.'),
                ], 'certification-finance'),
                $this->cat('Project Management', 'PMP, PRINCE2, and agile certification prep.', [
                    $this->cat('PMP Domains', 'People, process, and business environment.'),
                    $this->cat('Agile Scrum', 'Scrum events, artifacts, and servant leadership.'),
                ]),
                $this->cat('Language Proficiency', 'IELTS, TOEFL, and workplace communication exams.', [
                    $this->cat('IELTS Academic', 'Listening, reading, writing, and speaking mocks.'),
                    $this->cat('Business English', 'Professional writing and presentation assessments.'),
                ]),
            ], 'professional-certifications'),

            $this->cat('Corporate Hiring', 'Pre-employment screening used during candidate selection.', [
                $this->cat('Aptitude & Reasoning', 'Logical reasoning, verbal ability, numerical aptitude, and problem-solving.', [
                    $this->cat('Numerical Ability', 'Percentages, ratios, and data interpretation.'),
                    $this->cat('Logical Puzzles', 'Seating, arrangements, and analytical puzzles.'),
                    $this->cat('Verbal Ability', 'Grammar, vocabulary, and reading comprehension.'),
                ], 'corporate-aptitude'),
                $this->cat('Technical Screening', 'Domain-specific technical tests for engineering and data roles.', [
                    $this->cat('Software Engineering', 'DSA, OOP, system design basics, and coding.'),
                    $this->cat('Data Science Screening', 'Statistics, ML basics, and SQL caselets.'),
                    $this->cat('QA and Automation', 'Test design, Selenium, and API testing.'),
                ], 'corporate-technical'),
                $this->cat('Behavioral Assessments', 'Situational judgment and workplace behavior inventories.', [
                    $this->cat('Situational Judgment', 'Work scenario decision-making scenarios.'),
                    $this->cat('Personality Inventory', 'Trait-based workplace preference screening.'),
                ]),
                $this->cat('Role-Based Simulations', 'Job-task simulations for sales, support, and operations.', [
                    $this->cat('Customer Support', 'Ticket handling and communication scenarios.'),
                    $this->cat('Sales Aptitude', 'Objection handling and product pitching drills.'),
                ]),
            ], 'corporate-hiring'),

            $this->cat('Campus Recruitment', 'College placement and internship selection exams.', [
                $this->cat('IT Campus Drives', 'Coding and aptitude rounds for software internships.', [
                    $this->cat('Online Coding Round', 'Timed DSA and problem-solving assessments.'),
                    $this->cat('Written Technical', 'OS, DBMS, networks, and OOP MCQs.'),
                ]),
                $this->cat('Core Engineering Drives', 'Mechanical, electrical, and civil campus tests.', [
                    $this->cat('Mechanical Core', 'Thermodynamics, SOM, and manufacturing basics.'),
                    $this->cat('Electrical Core', 'Circuits, machines, and power systems.'),
                ]),
                $this->cat('Internship Assessments', 'Short screening tests for summer/winter internships.', [
                    $this->cat('General Aptitude', 'Speed-based quant and reasoning for interns.'),
                    $this->cat('Domain Basics', 'Entry-level domain knowledge checks.'),
                ]),
            ]),

            $this->cat('Skills Assessments', 'Standalone skill verification exams for learners and professionals.', [
                $this->cat('Programming Skills', 'Language-specific coding and concept assessments.', [
                    $this->cat('Python Skills', 'Syntax, libraries, and problem-solving in Python.'),
                    $this->cat('Java Skills', 'OOP, collections, and concurrency basics.'),
                    $this->cat('JavaScript Skills', 'ESNext, DOM, and async programming.'),
                ]),
                $this->cat('Office Productivity', 'Spreadsheet, presentation, and document productivity.', [
                    $this->cat('Excel Advanced', 'Formulas, pivot tables, and dashboards.'),
                    $this->cat('Google Workspace', 'Docs, Sheets, and collaborative workflows.'),
                ]),
                $this->cat('Soft Skills', 'Communication, teamwork, and professional etiquette.', [
                    $this->cat('Business Communication', 'Email, meetings, and stakeholder updates.'),
                    $this->cat('Presentation Skills', 'Structure, storytelling, and delivery.'),
                ]),
            ]),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private function cat(string $name, string $description, array $children = [], ?string $slug = null, string $status = 'active'): array
    {
        return [
            'name'             => $name,
            'description'      => $description,
            'status'           => $status,
            'slug'             => $slug,
            'meta_title'       => "{$name} — Exam Categories",
            'meta_description' => Str::limit("Browse {$name} exam categories. {$description}", 160),
            'meta_keywords'    => strtolower(str_replace([' and ', ' & ', '/'], [', ', ', ', ', '], $name)),
            'og_title'         => "{$name} Exams",
            'og_description'   => Str::limit($description, 160),
            'children'         => $children,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function insertTree(array $nodes, int $orgId, ?int $parentId, string $slugPrefix): void
    {
        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);

            $explicitSlug = $node['slug'] ?? null;
            $baseSlug     = Str::slug($node['name']);
            $slug         = $explicitSlug ?: ($slugPrefix !== '' ? "{$slugPrefix}-{$baseSlug}" : $baseSlug);
            $node['slug'] = $slug;

            $category = ExamCategory::firstOrCreate(
                [
                    'organization_id' => $orgId,
                    'name'            => $node['name'],
                    'parent_id'       => $parentId,
                ],
                array_merge($node, [
                    'organization_id' => $orgId,
                    'parent_id'       => $parentId,
                    'ai_generated'    => false,
                    'ai_improve'      => false,
                ])
            );

            if (! empty($children)) {
                $this->insertTree($children, $orgId, $category->id, $slug);
            }
        }
    }
}
