<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a production-like multi-level question category hierarchy for demo-org.
 *
 * Preserves names used by QuestionSeeder (Science, Physics, Mathematics, etc.)
 * while expanding into a broad academic and professional exam taxonomy.
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

        $tree = $this->tree();
        $this->insertTree($tree, $org->id, null, '');

        $count = QuestionCategory::forOrg($org->id)->count();
        $this->command->info("✓ QuestionCategorySeeder: {$count} categories seeded.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tree(): array
    {
        return [
            $this->cat('Science', 'Natural sciences covering physics, chemistry, biology, and earth systems.', [
                $this->cat('Physics', 'Matter, energy, motion, and fundamental forces.', [
                    $this->cat('Mechanics', 'Kinematics, dynamics, work, energy, and momentum.'),
                    $this->cat('Optics', 'Reflection, refraction, wave optics, and optical instruments.'),
                    $this->cat('Thermodynamics', 'Heat, temperature, laws of thermodynamics, and kinetic theory.'),
                    $this->cat('Electromagnetism', 'Electric fields, magnetic fields, circuits, and EM waves.'),
                    $this->cat('Modern Physics', 'Relativity, quantum physics, atomic and nuclear physics.'),
                    $this->cat('Waves and Oscillations', 'Simple harmonic motion, sound waves, and resonance.'),
                ]),
                $this->cat('Chemistry', 'Composition, structure, properties, and reactions of matter.', [
                    $this->cat('Organic Chemistry', 'Hydrocarbons, functional groups, isomerism, and reaction mechanisms.'),
                    $this->cat('Inorganic Chemistry', 'Periodic table, coordination compounds, and metallurgy.'),
                    $this->cat('Physical Chemistry', 'Chemical kinetics, equilibrium, electrochemistry, and thermodynamics.'),
                    $this->cat('Analytical Chemistry', 'Qualitative and quantitative analysis, spectroscopy, and chromatography.'),
                    $this->cat('Environmental Chemistry', 'Pollution, green chemistry, and atmospheric chemistry.'),
                ]),
                $this->cat('Biology', 'Structure, function, growth, and evolution of living organisms.', [
                    $this->cat('Microbiology', 'Bacteria, viruses, fungi, and laboratory identification methods.'),
                    $this->cat('Genetics', 'Mendelian inheritance, DNA, molecular genetics, and heredity.'),
                    $this->cat('Botany', 'Plant morphology, physiology, taxonomy, and ecology.'),
                    $this->cat('Zoology', 'Animal diversity, anatomy, physiology, and behavior.'),
                    $this->cat('Ecology', 'Ecosystems, biodiversity, population dynamics, and conservation.'),
                    $this->cat('Human Physiology', 'Organ systems, homeostasis, and clinical correlations.'),
                    $this->cat('Cell Biology', 'Cell structure, organelles, membranes, and cell division.'),
                ]),
                $this->cat('Earth Science', 'Geology, meteorology, oceanography, and planetary science.', [
                    $this->cat('Geology', 'Rocks, minerals, plate tectonics, and earth history.'),
                    $this->cat('Meteorology', 'Weather systems, climate, and atmospheric processes.'),
                ]),
            ]),

            $this->cat('Mathematics', 'Core mathematics for problem solving and competitive exams.', [
                $this->cat('Algebra', 'Equations, polynomials, matrices, sequences, and series.'),
                $this->cat('Geometry', 'Euclidean geometry, coordinate geometry, and proofs.'),
                $this->cat('Trigonometry', 'Trigonometric ratios, identities, equations, and applications.'),
                $this->cat('Calculus', 'Limits, differentiation, integration, and differential equations.'),
                $this->cat('Statistics and Probability', 'Descriptive statistics, distributions, and probability theory.'),
                $this->cat('Number Theory', 'Divisibility, primes, modular arithmetic, and Diophantine equations.'),
                $this->cat('Linear Algebra', 'Vectors, matrices, determinants, eigenvalues, and vector spaces.'),
                $this->cat('Discrete Mathematics', 'Sets, relations, graph theory, combinatorics, and logic.'),
            ]),

            $this->cat('Computer Science', 'Computing fundamentals, software, systems, and theory.', [
                $this->cat('Programming', 'Coding fundamentals, problem solving, and software construction.', [
                    $this->cat('Frontend Development', 'HTML, CSS, JavaScript, accessibility, and modern UI frameworks.', [
                        $this->cat('HTML and CSS', 'Semantic markup, layout systems, and responsive design.'),
                        $this->cat('JavaScript Frameworks', 'React, Vue, Angular, and component architecture.'),
                        $this->cat('State Management', 'Redux, Pinia, context patterns, and data fetching.'),
                        $this->cat('Web Performance', 'Core Web Vitals, bundling, lazy loading, and caching.'),
                        $this->cat('Accessibility', 'WCAG, ARIA, keyboard navigation, and inclusive design.'),
                    ]),
                    $this->cat('Backend Development', 'APIs, authentication, caching, queues, and server architecture.', [
                        $this->cat('REST API Design', 'Resource modeling, versioning, pagination, and error handling.'),
                        $this->cat('GraphQL', 'Schemas, resolvers, queries, mutations, and federation.'),
                        $this->cat('Authentication and Authorization', 'OAuth, JWT, RBAC, sessions, and SSO.'),
                        $this->cat('Microservices', 'Service boundaries, messaging, resilience, and observability.'),
                        $this->cat('Laravel', 'Routing, Eloquent, queues, testing, and deployment.'),
                        $this->cat('Node.js', 'Event loop, Express/Nest, streams, and package ecosystem.'),
                    ]),
                    $this->cat('Full Stack Development', 'End-to-end application design across client and server.'),
                ]),
                $this->cat('Database Management', 'Relational and NoSQL databases, design, and optimization.', [
                    $this->cat('SQL', 'Queries, joins, indexing, transactions, and stored procedures.'),
                    $this->cat('NoSQL Databases', 'Document, key-value, column, and graph data stores.'),
                    $this->cat('Database Design', 'Normalization, ER modeling, and schema design.'),
                    $this->cat('MySQL and MariaDB', 'Schema design, replication, and query tuning.'),
                    $this->cat('PostgreSQL', 'Advanced SQL, JSONB, indexing, and extensions.'),
                    $this->cat('MongoDB', 'Document modeling, aggregation, and sharding basics.'),
                    $this->cat('Redis', 'In-memory data structures, persistence, and pub/sub.'),
                ]),
                $this->cat('Data Structures and Algorithms', 'Arrays, trees, graphs, sorting, searching, and complexity.'),
                $this->cat('Operating Systems', 'Processes, memory, file systems, concurrency, and scheduling.'),
                $this->cat('Software Engineering', 'SDLC, design patterns, testing, and agile practices.'),
                $this->cat('Computer Architecture', 'CPU, memory hierarchy, instruction sets, and parallelism.'),
                $this->cat('Theory of Computation', 'Automata, grammars, computability, and complexity classes.'),
            ]),

            $this->cat('Programming Languages', 'Language syntax, semantics, and ecosystem fundamentals.', [
                $this->cat('Python', 'Syntax, standard library, OOP, and popular frameworks.'),
                $this->cat('Java', 'OOP, JVM, collections, concurrency, and enterprise patterns.'),
                $this->cat('JavaScript', 'ESNext, DOM, asynchronous programming, and tooling.'),
                $this->cat('TypeScript', 'Static typing, interfaces, generics, and toolchain.'),
                $this->cat('C and C++', 'Pointers, memory management, STL, and systems programming.'),
                $this->cat('PHP', 'Server-side scripting, Laravel ecosystem, and web APIs.'),
                $this->cat('Go', 'Concurrency, packages, networking, and cloud-native services.'),
                $this->cat('Rust', 'Ownership, borrowing, safety, and systems performance.'),
                $this->cat('C#', '.NET runtime, LINQ, ASP.NET, and Windows/cloud apps.'),
                $this->cat('Ruby', 'Ruby syntax, Rails conventions, and scripting.'),
                $this->cat('Kotlin', 'JVM interoperability, coroutines, and Android development.'),
                $this->cat('Swift', 'iOS/macOS development, SwiftUI, and Apple frameworks.'),
            ]),

            $this->cat('Mobile Development', 'Native and cross-platform mobile application development.', [
                $this->cat('Android Development', 'Activities, Jetpack, Kotlin, and Play Store practices.'),
                $this->cat('iOS Development', 'UIKit, SwiftUI, App Store guidelines, and device APIs.'),
                $this->cat('React Native', 'Cross-platform components, navigation, and native modules.'),
                $this->cat('Flutter', 'Dart, widgets, state management, and platform channels.'),
                $this->cat('Mobile UI Patterns', 'Navigation, gestures, offline-first UX, and responsiveness.'),
                $this->cat('App Security', 'Secure storage, certificate pinning, and privacy controls.'),
            ]),

            $this->cat('DevOps and Cloud', 'CI/CD, infrastructure, and cloud platform operations.', [
                $this->cat('Linux Administration', 'Shell, processes, networking, and system hardening.'),
                $this->cat('Docker', 'Images, containers, Compose, and multi-stage builds.'),
                $this->cat('Kubernetes', 'Pods, services, deployments, and cluster operations.'),
                $this->cat('CI CD Pipelines', 'GitHub Actions, Jenkins, testing gates, and releases.'),
                $this->cat('AWS', 'EC2, S3, IAM, RDS, Lambda, and well-architected practices.'),
                $this->cat('Azure', 'App Services, AKS, Entra ID, and Azure DevOps.'),
                $this->cat('Google Cloud', 'Compute Engine, GKE, BigQuery, and IAM.'),
                $this->cat('Infrastructure as Code', 'Terraform, Ansible, CloudFormation, and GitOps.'),
                $this->cat('Observability', 'Logging, metrics, tracing, and alerting.'),
            ]),

            $this->cat('Artificial Intelligence', 'Intelligent systems, knowledge representation, and AI applications.', [
                $this->cat('AI Fundamentals', 'Search, agents, knowledge bases, and reasoning systems.'),
                $this->cat('Natural Language Processing', 'Tokenization, embeddings, classification, and generation.'),
                $this->cat('Computer Vision', 'Image classification, detection, segmentation, and OCR.'),
                $this->cat('Expert Systems', 'Rule engines, inference, and knowledge engineering.'),
                $this->cat('Generative AI', 'LLMs, prompt engineering, RAG, and evaluation.'),
                $this->cat('AI Ethics', 'Bias, fairness, privacy, transparency, and governance.'),
            ]),

            $this->cat('Machine Learning', 'Statistical learning, model training, and MLOps.', [
                $this->cat('Supervised Learning', 'Regression, classification, and ensemble methods.'),
                $this->cat('Unsupervised Learning', 'Clustering, dimensionality reduction, and anomaly detection.'),
                $this->cat('Deep Learning', 'Neural networks, CNNs, RNNs, transformers, and training.'),
                $this->cat('Feature Engineering', 'Encoding, scaling, selection, and pipeline design.'),
                $this->cat('Model Evaluation', 'Metrics, cross-validation, bias-variance, and A/B testing.'),
                $this->cat('MLOps', 'Experiment tracking, deployment, monitoring, and model registries.'),
                $this->cat('Reinforcement Learning', 'MDPs, policy gradients, Q-learning, and applications.'),
            ]),

            $this->cat('Cybersecurity', 'Protecting systems, networks, applications, and data.', [
                $this->cat('Network Security', 'Firewalls, IDS/IPS, VPN, and zero trust.'),
                $this->cat('Application Security', 'OWASP Top 10, secure coding, and threat modeling.'),
                $this->cat('Cryptography', 'Symmetric/asymmetric crypto, hashing, PKI, and TLS.'),
                $this->cat('Ethical Hacking', 'Reconnaissance, exploitation basics, and reporting.'),
                $this->cat('Security Operations', 'SIEM, incident response, forensics, and SOC workflows.'),
                $this->cat('Identity and Access Management', 'MFA, privileged access, and identity federation.'),
                $this->cat('Compliance and Governance', 'ISO 27001, GDPR, risk management, and audits.'),
            ]),

            $this->cat('Networking', 'Computer networks, protocols, and infrastructure design.', [
                $this->cat('Network Fundamentals', 'OSI model, TCP/IP, addressing, and switching.'),
                $this->cat('Routing and Switching', 'Static/dynamic routing, VLANs, and spanning tree.'),
                $this->cat('Wireless Networking', 'Wi-Fi standards, roaming, and wireless security.'),
                $this->cat('Network Troubleshooting', 'Diagnostics, packet analysis, and performance tuning.'),
                $this->cat('Network Design', 'LAN/WAN topologies, redundancy, and capacity planning.'),
                $this->cat('Protocols and Standards', 'HTTP, DNS, DHCP, BGP, and common RFCs.'),
            ]),

            $this->cat('Commerce', 'Business operations, trade, and commercial practices.', [
                $this->cat('Business Studies', 'Organization, management principles, and entrepreneurship.'),
                $this->cat('Marketing', 'Market research, branding, digital marketing, and consumer behavior.'),
                $this->cat('Business Law', 'Contracts, company law, consumer protection, and compliance.'),
                $this->cat('International Trade', 'Import/export, trade barriers, and global markets.'),
                $this->cat('E-Commerce', 'Online marketplaces, payments, logistics, and conversion.'),
                $this->cat('Supply Chain Management', 'Procurement, inventory, logistics, and demand planning.'),
            ]),

            $this->cat('Accounting', 'Financial recording, reporting, and control systems.', [
                $this->cat('Financial Accounting', 'Journal entries, ledgers, trial balance, and final accounts.'),
                $this->cat('Cost Accounting', 'Costing methods, variance analysis, and cost control.'),
                $this->cat('Management Accounting', 'Budgets, CVP analysis, and managerial reporting.'),
                $this->cat('Taxation', 'Income tax, GST/VAT concepts, and tax planning basics.'),
                $this->cat('Auditing', 'Internal controls, audit procedures, and assurance standards.'),
                $this->cat('Corporate Accounting', 'Company accounts, amalgamation, and financial statements.'),
            ]),

            $this->cat('Finance', 'Corporate finance, markets, investments, and risk.', [
                $this->cat('Corporate Finance', 'Capital budgeting, working capital, and capital structure.'),
                $this->cat('Investment Analysis', 'Equity/debt valuation, portfolio theory, and ratios.'),
                $this->cat('Financial Markets', 'Stock markets, bonds, derivatives, and market microstructure.'),
                $this->cat('Banking and Insurance', 'Retail/corporate banking, underwriting, and risk pooling.'),
                $this->cat('Personal Finance', 'Budgeting, saving, credit, and retirement planning.'),
                $this->cat('Financial Risk Management', 'Market, credit, operational risk, and hedging.'),
            ]),

            $this->cat('Economics', 'Microeconomics, macroeconomics, and applied economic analysis.', [
                $this->cat('Microeconomics', 'Demand, supply, elasticity, markets, and consumer theory.'),
                $this->cat('Macroeconomics', 'GDP, inflation, unemployment, fiscal and monetary policy.'),
                $this->cat('Indian Economy', 'Growth, planning, reforms, and sectoral composition.'),
                $this->cat('International Economics', 'Trade theory, exchange rates, and balance of payments.'),
                $this->cat('Development Economics', 'Poverty, inequality, human development, and growth models.'),
                $this->cat('Public Finance', 'Taxation, public expenditure, deficits, and fiscal federalism.'),
            ]),

            $this->cat('History', 'Political, social, and cultural history across eras and regions.', [
                $this->cat('Ancient History', 'Early civilizations, empires, and archaeological sources.'),
                $this->cat('Medieval History', 'Kingdoms, empires, society, and cultural exchange.'),
                $this->cat('Modern History', 'Industrialization, nationalism, world wars, and decolonization.'),
                $this->cat('Indian History', 'Ancient to modern India, freedom struggle, and post-independence.'),
                $this->cat('World History', 'Global events, revolutions, and international relations.'),
                $this->cat('Art and Culture', 'Architecture, literature, performing arts, and heritage.'),
            ]),

            $this->cat('Geography', 'Physical and human geography with map-based skills.', [
                $this->cat('Physical Geography', 'Landforms, climate, soils, and natural hazards.'),
                $this->cat('Human Geography', 'Population, settlement, urbanization, and migration.'),
                $this->cat('Indian Geography', 'Physiography, climate, resources, and regional planning.'),
                $this->cat('World Geography', 'Continents, oceans, climates, and geopolitical regions.'),
                $this->cat('Economic Geography', 'Agriculture, industry, trade, and resource distribution.'),
                $this->cat('Environmental Geography', 'Ecosystems, climate change, and sustainability.'),
            ]),

            $this->cat('Political Science', 'Political theory, institutions, and comparative politics.', [
                $this->cat('Political Theory', 'Concepts of state, rights, justice, liberty, and equality.'),
                $this->cat('Indian Constitution', 'Preamble, fundamental rights, DPSPs, and amendments.'),
                $this->cat('Indian Polity', 'Parliament, executive, judiciary, and federal structure.'),
                $this->cat('International Relations', 'Diplomacy, organizations, geopolitics, and foreign policy.'),
                $this->cat('Public Administration', 'Bureaucracy, governance, policy, and local government.'),
                $this->cat('Comparative Politics', 'Political systems, parties, elections, and regimes.'),
            ]),

            $this->cat('English', 'Language proficiency for academic and competitive exams.', [
                $this->cat('Grammar', 'Tenses, articles, prepositions, voice, and narration.'),
                $this->cat('Vocabulary', 'Synonyms, antonyms, idioms, phrases, and word usage.'),
                $this->cat('Reading Comprehension', 'Passages, inference, tone, and critical reading.'),
                $this->cat('Writing Skills', 'Essays, letters, précis, and formal communication.'),
                $this->cat('Verbal Ability', 'Sentence correction, para jumbles, and cloze tests.'),
                $this->cat('Literature Basics', 'Genres, literary devices, and major authors.'),
            ]),

            $this->cat('Aptitude', 'Quantitative aptitude for competitive and placement exams.', [
                $this->cat('Number System', 'Divisibility, HCF/LCM, remainders, and base systems.'),
                $this->cat('Percentages and Ratios', 'Percentage change, ratios, proportions, and mixtures.'),
                $this->cat('Profit and Loss', 'Markups, discounts, partnerships, and successive deals.'),
                $this->cat('Time Speed Distance', 'Relative speed, boats, trains, and races.'),
                $this->cat('Time and Work', 'Work rates, pipes, cisterns, and efficiency.'),
                $this->cat('Simple and Compound Interest', 'Interest calculations and growth applications.'),
                $this->cat('Data Interpretation', 'Tables, charts, graphs, and caselets.'),
                $this->cat('Permutation and Combination', 'Counting principles, arrangements, and selections.'),
            ]),

            $this->cat('Reasoning', 'Logical and analytical reasoning for competitive exams.', [
                $this->cat('Logical Reasoning', 'Syllogisms, statements, assumptions, and conclusions.'),
                $this->cat('Analytical Reasoning', 'Puzzles, seating arrangements, and scheduling.'),
                $this->cat('Verbal Reasoning', 'Analogies, classifications, and critical reasoning.'),
                $this->cat('Non Verbal Reasoning', 'Series, mirrors, paper folding, and figure matrices.'),
                $this->cat('Coding Decoding', 'Letter/number coding and pattern recognition.'),
                $this->cat('Blood Relations', 'Family trees and relationship puzzles.'),
                $this->cat('Directions and Distances', 'Direction sense and path tracking.'),
            ]),

            $this->cat('General Knowledge', 'Current affairs and static GK for competitive exams.', [
                $this->cat('Current Affairs', 'National and international news, appointments, and events.'),
                $this->cat('Static GK', 'Awards, books, sports, days, and important institutions.'),
                $this->cat('Science and Technology GK', 'Discoveries, space, defense, and digital initiatives.'),
                $this->cat('Indian Polity GK', 'Constitutional bodies, schemes, and governance facts.'),
                $this->cat('Economy GK', 'Budget basics, banking terms, and economic indicators.'),
                $this->cat('Sports and Culture', 'Tournaments, records, heritage, and festivals.'),
                $this->cat('Environment and Ecology GK', 'Biodiversity, climate agreements, and conservation.'),
            ]),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    private function cat(string $name, string $description, array $children = [], string $status = 'active'): array
    {
        return [
            'name'             => $name,
            'description'      => $description,
            'status'           => $status,
            'meta_title'       => "{$name} — Question Categories",
            'meta_description' => Str::limit("Browse {$name} question categories. {$description}", 160),
            'meta_keywords'    => strtolower(str_replace([' and ', ' & ', '/'], [', ', ', ', ', '], $name)),
            'og_title'         => "{$name} Questions",
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

            $baseSlug = Str::slug($node['name']);
            $slug     = $slugPrefix !== '' ? "{$slugPrefix}-{$baseSlug}" : $baseSlug;

            $category = QuestionCategory::firstOrCreate(
                [
                    'organization_id' => $orgId,
                    'name'            => $node['name'],
                    'parent_id'       => $parentId,
                ],
                array_merge($node, [
                    'organization_id' => $orgId,
                    'parent_id'       => $parentId,
                    'slug'            => $slug,
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
