<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;

/**
 * QuestionCategorySeeder
 *
 * Seeds a realistic multi-level category tree for the Question module.
 *
 * Tree structure:
 *   Science
 *     ├── Physics
 *     │     ├── Mechanics
 *     │     └── Optics
 *     ├── Chemistry
 *     └── Biology
 *           ├── Microbiology
 *           └── Genetics
 *   Mathematics
 *     ├── Algebra
 *     └── Geometry
 *   Computer Science
 *     ├── Programming
 *     │     ├── Frontend Development
 *     │     └── Backend Development
 *     └── Database Management
 */
class QuestionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->first();

        if (! $org) {
            $this->command->warn('QuestionCategorySeeder: demo-org not found. Skipping.');
            return;
        }

        $orgId = $org->id;

        // ── Tree definition ───────────────────────────────────────────────────
        $tree = [
            [
                'name'             => 'Science',
                'description'      => 'Covers all natural science disciplines including physics, chemistry, biology, and interdisciplinary research branches.',
                'status'           => 'active',
                'slug'             => 'science',
                'meta_title'       => 'Science — Question Categories',
                'meta_description' => 'Browse all Science question categories including Physics, Chemistry, and Biology.',
                'meta_keywords'    => 'science, physics, chemistry, biology, natural science',
                'og_title'         => 'Science Questions',
                'og_description'   => 'Explore science questions across multiple disciplines.',
                'children' => [
                    [
                        'name'             => 'Physics',
                        'description'      => 'Study of matter, energy, and the fundamental forces of nature. Covers classical mechanics, optics, thermodynamics, and modern physics.',
                        'status'           => 'active',
                        'slug'             => 'science-physics',
                        'meta_title'       => 'Physics — Question Categories',
                        'meta_description' => 'Physics questions covering mechanics, optics, thermodynamics, and quantum physics.',
                        'meta_keywords'    => 'physics, mechanics, optics, thermodynamics, quantum',
                        'og_title'         => 'Physics Questions',
                        'og_description'   => 'Test your knowledge of physics across all major sub-topics.',
                        'children' => [
                            [
                                'name'             => 'Mechanics',
                                'description'      => 'Classical mechanics including Newton\'s laws, kinematics, dynamics, work, energy, and momentum.',
                                'status'           => 'active',
                                'slug'             => 'science-physics-mechanics',
                                'meta_title'       => 'Mechanics — Physics Questions',
                                'meta_description' => 'Questions on classical mechanics, Newton\'s laws, kinematics, and dynamics.',
                                'meta_keywords'    => 'mechanics, newton, kinematics, dynamics, force',
                                'og_title'         => 'Mechanics Questions',
                                'og_description'   => 'Mechanics questions covering force, motion, and energy.',
                                'children' => [],
                            ],
                            [
                                'name'             => 'Optics',
                                'description'      => 'Study of light, reflection, refraction, diffraction, and optical instruments.',
                                'status'           => 'active',
                                'slug'             => 'science-physics-optics',
                                'meta_title'       => 'Optics — Physics Questions',
                                'meta_description' => 'Questions on light, lenses, mirrors, and wave optics.',
                                'meta_keywords'    => 'optics, light, refraction, reflection, lens',
                                'og_title'         => 'Optics Questions',
                                'og_description'   => 'Explore optics questions from basic reflection to wave interference.',
                                'children' => [],
                            ],
                        ],
                    ],
                    [
                        'name'             => 'Chemistry',
                        'description'      => 'Organic, inorganic, and physical chemistry topics with lab-ready modules covering reactions, bonding, and thermodynamics.',
                        'status'           => 'active',
                        'slug'             => 'science-chemistry',
                        'meta_title'       => 'Chemistry — Question Categories',
                        'meta_description' => 'Chemistry questions on organic, inorganic, and physical chemistry.',
                        'meta_keywords'    => 'chemistry, organic, inorganic, reactions, bonding',
                        'og_title'         => 'Chemistry Questions',
                        'og_description'   => 'Chemistry questions spanning all major topics.',
                        'children' => [],
                    ],
                    [
                        'name'             => 'Biology',
                        'description'      => 'Cell biology, genetics, anatomy, ecology, and life sciences fundamentals for competitive exams.',
                        'status'           => 'active',
                        'slug'             => 'science-biology',
                        'meta_title'       => 'Biology — Question Categories',
                        'meta_description' => 'Biology questions covering cells, genetics, anatomy, and ecology.',
                        'meta_keywords'    => 'biology, genetics, ecology, cell, anatomy',
                        'og_title'         => 'Biology Questions',
                        'og_description'   => 'Biology questions across all life science sub-topics.',
                        'children' => [
                            [
                                'name'             => 'Microbiology',
                                'description'      => 'Study of microorganisms including bacteria, fungi, viruses, and laboratory methods for identification.',
                                'status'           => 'active',
                                'slug'             => 'science-biology-microbiology',
                                'meta_title'       => 'Microbiology — Biology Questions',
                                'meta_description' => 'Microbiology questions on bacteria, viruses, fungi, and lab techniques.',
                                'meta_keywords'    => 'microbiology, bacteria, virus, fungi, lab',
                                'og_title'         => 'Microbiology Questions',
                                'og_description'   => 'Test your microbiology knowledge with detailed questions.',
                                'children' => [],
                            ],
                            [
                                'name'             => 'Genetics',
                                'description'      => 'Mendelian genetics, molecular genetics, DNA replication, mutations, and heredity patterns.',
                                'status'           => 'active',
                                'slug'             => 'science-biology-genetics',
                                'meta_title'       => 'Genetics — Biology Questions',
                                'meta_description' => 'Genetics questions on Mendelian inheritance, DNA, and molecular biology.',
                                'meta_keywords'    => 'genetics, DNA, inheritance, heredity, Mendel',
                                'og_title'         => 'Genetics Questions',
                                'og_description'   => 'Genetics questions from basic inheritance to molecular genetics.',
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name'             => 'Mathematics',
                'description'      => 'Core mathematics subjects for analytical thinking and problem solving. Covers algebra, geometry, calculus, and statistics.',
                'status'           => 'active',
                'slug'             => 'mathematics',
                'meta_title'       => 'Mathematics — Question Categories',
                'meta_description' => 'Mathematics questions on algebra, geometry, calculus, and more.',
                'meta_keywords'    => 'mathematics, algebra, geometry, calculus, statistics',
                'og_title'         => 'Mathematics Questions',
                'og_description'   => 'Explore mathematics questions across all branches.',
                'children' => [
                    [
                        'name'             => 'Algebra',
                        'description'      => 'Linear equations, quadratic equations, polynomials, matrices, and abstract algebraic structures.',
                        'status'           => 'active',
                        'slug'             => 'mathematics-algebra',
                        'meta_title'       => 'Algebra — Mathematics Questions',
                        'meta_description' => 'Algebra questions on equations, polynomials, and matrices.',
                        'meta_keywords'    => 'algebra, equations, polynomials, matrices, linear',
                        'og_title'         => 'Algebra Questions',
                        'og_description'   => 'Algebra questions from linear equations to abstract structures.',
                        'children' => [],
                    ],
                    [
                        'name'             => 'Geometry',
                        'description'      => 'Euclidean geometry, coordinate geometry, shapes, proofs, and trigonometry applications.',
                        'status'           => 'active',
                        'slug'             => 'mathematics-geometry',
                        'meta_title'       => 'Geometry — Mathematics Questions',
                        'meta_description' => 'Geometry questions on shapes, proofs, and coordinate systems.',
                        'meta_keywords'    => 'geometry, euclidean, coordinate, shapes, proofs',
                        'og_title'         => 'Geometry Questions',
                        'og_description'   => 'Geometry questions spanning Euclidean and coordinate geometry.',
                        'children' => [],
                    ],
                ],
            ],
            [
                'name'             => 'Computer Science',
                'description'      => 'Programming, data structures, algorithms, databases, networking, and software engineering categories.',
                'status'           => 'active',
                'slug'             => 'computer-science',
                'meta_title'       => 'Computer Science — Question Categories',
                'meta_description' => 'Computer science questions on programming, databases, and algorithms.',
                'meta_keywords'    => 'computer science, programming, algorithms, databases, networking',
                'og_title'         => 'Computer Science Questions',
                'og_description'   => 'Computer science questions across all major domains.',
                'children' => [
                    [
                        'name'             => 'Programming',
                        'description'      => 'Fundamentals of coding, data structures, algorithms, design patterns, and software engineering principles.',
                        'status'           => 'active',
                        'slug'             => 'computer-science-programming',
                        'meta_title'       => 'Programming — CS Questions',
                        'meta_description' => 'Programming questions on data structures, algorithms, and design patterns.',
                        'meta_keywords'    => 'programming, algorithms, data structures, design patterns',
                        'og_title'         => 'Programming Questions',
                        'og_description'   => 'Programming questions from basics to advanced algorithms.',
                        'children' => [
                            [
                                'name'             => 'Frontend Development',
                                'description'      => 'HTML, CSS, JavaScript, React, Vue, browser APIs, and web performance optimization.',
                                'status'           => 'active',
                                'slug'             => 'cs-programming-frontend',
                                'meta_title'       => 'Frontend Development — Programming Questions',
                                'meta_description' => 'Frontend questions on HTML, CSS, JavaScript, and modern frameworks.',
                                'meta_keywords'    => 'frontend, HTML, CSS, JavaScript, React, Vue',
                                'og_title'         => 'Frontend Dev Questions',
                                'og_description'   => 'Frontend development questions for web developers.',
                                'children' => [],
                            ],
                            [
                                'name'             => 'Backend Development',
                                'description'      => 'Server-side programming, REST APIs, authentication, caching, queues, and backend architecture.',
                                'status'           => 'active',
                                'slug'             => 'cs-programming-backend',
                                'meta_title'       => 'Backend Development — Programming Questions',
                                'meta_description' => 'Backend questions on APIs, authentication, and server architecture.',
                                'meta_keywords'    => 'backend, API, REST, server, authentication, Laravel',
                                'og_title'         => 'Backend Dev Questions',
                                'og_description'   => 'Backend development questions for server-side developers.',
                                'children' => [],
                            ],
                        ],
                    ],
                    [
                        'name'             => 'Database Management',
                        'description'      => 'SQL, NoSQL, normalization, indexing, ACID properties, query optimization, and database design.',
                        'status'           => 'active',
                        'slug'             => 'computer-science-databases',
                        'meta_title'       => 'Database Management — CS Questions',
                        'meta_description' => 'Database questions on SQL, normalization, indexing, and ACID properties.',
                        'meta_keywords'    => 'database, SQL, NoSQL, normalization, indexing, ACID',
                        'og_title'         => 'Database Management Questions',
                        'og_description'   => 'Database management questions from SQL basics to advanced optimization.',
                        'children' => [],
                    ],
                ],
            ],
        ];

        // ── Recursive insert ───────────────────────────────────────────────────
        $this->insertTree($tree, $orgId, null);

        $this->command->info('✓ QuestionCategorySeeder: Category tree seeded successfully.');
    }

    /**
     * Recursively insert categories into the database.
     *
     * @param  array<int, array>  $nodes
     * @param  int                $orgId
     * @param  int|null           $parentId
     */
    private function insertTree(array $nodes, int $orgId, ?int $parentId): void
    {
        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);

            $category = QuestionCategory::firstOrCreate(
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
                $this->insertTree($children, $orgId, $category->id);
            }
        }
    }
}
