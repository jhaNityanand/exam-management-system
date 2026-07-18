<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Database\Seeders\Support\SeedImageLibrary;
use Illuminate\Database\Seeder;
use Throwable;

/**
 * Seeds realistic published blog posts for demo-org with gallery banners.
 */
class BlogSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        $editor = $this->demoEditor();

        if (! $org || ! $editor) {
            $this->command?->warn('BlogSeeder: demo-org or editor missing. Skipping.');

            return;
        }

        $images = new SeedImageLibrary;
        $purged = $images->purge($org->id, 'blog');
        $this->command?->info("BlogSeeder: purged {$purged} previously seeded blog image(s).");

        $categories = BlogCategory::forOrg($org->id)->get()->keyBy('slug');
        $tags = BlogTag::forOrg($org->id)->get()->keyBy('name');
        $seeded = 0;

        foreach ($this->posts() as $post) {
            $category = $categories->get($post['category_slug']);

            if (! $category) {
                $this->command?->warn("BlogSeeder: category [{$post['category_slug']}] not found. Skipping {$post['slug']}.");

                continue;
            }

            try {
                $banner = $images->store(
                    $org->id,
                    $post['slug'],
                    $editor->id,
                    'blog',
                    [
                        'alt_text' => $post['title'],
                        'description' => 'Banner for '.$post['title'],
                    ]
                );
            } catch (Throwable $e) {
                $this->command?->warn("BlogSeeder: image download failed for {$post['slug']}: {$e->getMessage()}");
                $banner = null;
            }

            $blog = Blog::updateOrCreate(
                [
                    'organization_id' => $org->id,
                    'slug' => $post['slug'],
                ],
                [
                    'organization_id' => $org->id,
                    'blog_category_id' => $category->id,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'content' => $post['content'],
                    'banner_image_id' => $banner?->id,
                    'author_id' => $editor->id,
                    'author_name' => $editor->name,
                    'status' => Blog::STATUS_PUBLISHED,
                    'published_at' => now()->subDays(random_int(3, 120)),
                    'view_count' => random_int(40, 980),
                    'seo_title' => $post['seo_title'],
                    'seo_description' => $post['seo_description'],
                    'seo_keywords' => $post['seo_keywords'],
                    'og_title' => $post['og_title'],
                    'og_description' => $post['og_description'],
                    'og_image_id' => $banner?->id,
                    'canonical_url' => null,
                    'robots' => 'index,follow',
                    'schema_markup' => json_encode([
                        '@context' => 'https://schema.org',
                        '@type' => 'BlogPosting',
                        'headline' => $post['title'],
                        'description' => $post['seo_description'] ?? $post['excerpt'],
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'ai_generated' => false,
                    'ai_improve' => false,
                    'created_by' => $editor->id,
                ]
            );

            if ($banner) {
                $blog->banners()->sync([$banner->id => ['sort_order' => 0]]);
            }

            $tagIds = collect($post['tags'])
                ->map(fn (string $name) => $tags->get($name)?->id)
                ->filter()
                ->values()
                ->all();

            $blog->tags()->sync($tagIds);
            $seeded++;
        }

        $this->command?->info("BlogSeeder: seeded {$seeded} blog posts with gallery banners.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function posts(): array
    {
        return [
            [
                'slug'            => 'getting-started-laravel-11-service-container',
                'category_slug'   => 'laravel',
                'title'           => 'Getting Started with Laravel 11 Service Container',
                'excerpt'         => 'The service container is Laravel\'s dependency injection engine. Learn how bindings, resolution, and contextual injection keep applications testable and modular.',
                'tags'            => ['Laravel', 'PHP 8', 'Testing'],
                'seo_title'       => 'Laravel 11 Service Container Guide',
                'seo_description' => 'A practical introduction to Laravel\'s service container: bindings, singletons, contextual injection, and testing strategies.',
                'seo_keywords'    => 'laravel, service container, dependency injection, bindings',
                'og_title'        => 'Laravel 11 Service Container Explained',
                'og_description'  => 'Understand how Laravel resolves classes automatically and how to register custom bindings.',
                'content'         => $this->contentLaravelServiceContainer(),
            ],
            [
                'slug'            => 'eloquent-performance-tips-every-developer-should-know',
                'category_slug'   => 'laravel-eloquent',
                'title'           => 'Eloquent Performance Tips Every Developer Should Know',
                'excerpt'         => 'N+1 queries and eager-loading mistakes are the most common Eloquent performance issues. These patterns help you write faster database access without abandoning the ORM.',
                'tags'            => ['Laravel', 'Eloquent', 'Performance', 'MySQL'],
                'seo_title'       => 'Eloquent Performance Tips',
                'seo_description' => 'Practical Eloquent optimization: eager loading, chunking, selective columns, and query profiling.',
                'seo_keywords'    => 'eloquent, laravel, performance, n+1, eager loading',
                'og_title'        => 'Eloquent Performance Tips',
                'og_description'  => 'Stop N+1 queries and reduce memory usage with proven Eloquent patterns.',
                'content'         => $this->contentEloquentPerformance(),
            ],
            [
                'slug'            => 'building-rest-apis-laravel-sanctum',
                'category_slug'   => 'apis-integrations',
                'title'           => 'Building REST APIs with Laravel Sanctum',
                'excerpt'         => 'Sanctum provides a lightweight token and SPA authentication layer for Laravel APIs. This guide walks through token issuance, middleware, and common security pitfalls.',
                'tags'            => ['Laravel', 'REST API', 'OWASP'],
                'seo_title'       => 'REST APIs with Laravel Sanctum',
                'seo_description' => 'Build secure REST APIs in Laravel using Sanctum for token authentication and SPA sessions.',
                'seo_keywords'    => 'laravel sanctum, rest api, token authentication',
                'og_title'        => 'Laravel Sanctum REST API Tutorial',
                'og_description'  => 'Token-based API authentication with Laravel Sanctum from setup to production.',
                'content'         => $this->contentSanctumApi(),
            ],
            [
                'slug'            => 'php-83-features-improve-everyday-code',
                'category_slug'   => 'php-modern-php',
                'title'           => 'PHP 8.3 Features That Improve Everyday Code',
                'excerpt'         => 'PHP 8.3 adds typed class constants, the json_validate() helper, and override attributes. Small language upgrades that compound into cleaner, safer codebases.',
                'tags'            => ['PHP 8', 'Laravel'],
                'seo_title'       => 'PHP 8.3 Features for Developers',
                'seo_description' => 'Explore PHP 8.3 improvements: typed constants, json_validate, override attribute, and randomizer enhancements.',
                'seo_keywords'    => 'php 8.3, typed constants, modern php',
                'og_title'        => 'PHP 8.3 Everyday Improvements',
                'og_description'  => 'Language features in PHP 8.3 that make daily development more expressive.',
                'content'         => $this->contentPhp83(),
            ],
            [
                'slug'            => 'designing-scalable-nodejs-microservices',
                'category_slug'   => 'javascript-nodejs',
                'title'           => 'Designing Scalable Node.js Microservices',
                'excerpt'         => 'Node.js excels at I/O-bound services, but scalability requires deliberate boundaries, observability, and failure handling. Learn a pragmatic microservice layout.',
                'tags'            => ['Node.js', 'Express', 'System Design', 'Docker'],
                'seo_title'       => 'Scalable Node.js Microservices',
                'seo_description' => 'Architecture patterns for Node.js microservices: service boundaries, messaging, health checks, and deployment.',
                'seo_keywords'    => 'node.js, microservices, express, scalability',
                'og_title'        => 'Node.js Microservices Design',
                'og_description'  => 'Practical guidance for building maintainable Node.js microservices.',
                'content'         => $this->contentNodeMicroservices(),
            ],
            [
                'slug'            => 'react-server-components-explained-simply',
                'category_slug'   => 'javascript-react',
                'title'           => 'React Server Components Explained Simply',
                'excerpt'         => 'Server Components render on the server and ship minimal JavaScript to the browser. Understand when to use them alongside Client Components.',
                'tags'            => ['React', 'Performance'],
                'seo_title'       => 'React Server Components Guide',
                'seo_description' => 'A clear explanation of React Server Components, their benefits, and integration with Client Components.',
                'seo_keywords'    => 'react server components, rsc, next.js',
                'og_title'        => 'React Server Components Explained',
                'og_description'  => 'What Server Components are and how they change React application architecture.',
                'content'         => $this->contentReactRsc(),
            ],
            [
                'slug'            => 'vue-3-composition-api-patterns-large-apps',
                'category_slug'   => 'javascript-vuejs',
                'title'           => 'Vue 3 Composition API Patterns for Large Apps',
                'excerpt'         => 'Composable functions, provide/inject, and store extraction help Vue 3 applications scale without turning every component into a monolith.',
                'tags'            => ['Vue.js', 'Testing'],
                'seo_title'       => 'Vue 3 Composition API Patterns',
                'seo_description' => 'Scalable Vue 3 patterns: composables, state modules, typed props, and testable component design.',
                'seo_keywords'    => 'vue 3, composition api, composables',
                'og_title'        => 'Vue 3 Composition API at Scale',
                'og_description'  => 'Organize large Vue 3 codebases with composables and clear state boundaries.',
                'content'         => $this->contentVueComposition(),
            ],
            [
                'slug'            => 'indexing-strategies-save-mysql-query-time',
                'category_slug'   => 'databases-mysql',
                'title'           => 'Indexing Strategies That Save MySQL Query Time',
                'excerpt'         => 'The right composite index can turn a full table scan into a millisecond lookup. Learn how to analyze slow queries and design indexes that match real access patterns.',
                'tags'            => ['MySQL', 'Performance', 'Eloquent'],
                'seo_title'       => 'MySQL Indexing Strategies',
                'seo_description' => 'Composite indexes, covering indexes, and EXPLAIN-driven tuning for faster MySQL queries.',
                'seo_keywords'    => 'mysql, indexing, query optimization, explain',
                'og_title'        => 'MySQL Indexing That Works',
                'og_description'  => 'Practical indexing strategies backed by EXPLAIN analysis.',
                'content'         => $this->contentMysqlIndexing(),
            ],
            [
                'slug'            => 'docker-compose-workflows-laravel-teams',
                'category_slug'   => 'devops',
                'title'           => 'Docker Compose Workflows for Laravel Teams',
                'excerpt'         => 'A consistent local stack—PHP, MySQL, Redis, and queue workers—reduces onboarding friction. Docker Compose makes that reproducible across every developer machine.',
                'tags'            => ['Docker', 'Laravel', 'CI/CD'],
                'seo_title'       => 'Docker Compose for Laravel',
                'seo_description' => 'Set up a Laravel development environment with Docker Compose, volumes, and queue worker services.',
                'seo_keywords'    => 'docker compose, laravel, dev environment',
                'og_title'        => 'Laravel Docker Compose Setup',
                'og_description'  => 'Reproducible Laravel local development with Docker Compose.',
                'content'         => $this->contentDockerLaravel(),
            ],
            [
                'slug'            => 'owasp-top-10-practical-fixes-laravel-apps',
                'category_slug'   => 'cybersecurity',
                'title'           => 'OWASP Top 10 Practical Fixes for Laravel Apps',
                'excerpt'         => 'Laravel ships strong defaults, but misconfiguration still causes real breaches. Map OWASP Top 10 risks to concrete Laravel mitigations your team can audit today.',
                'tags'            => ['OWASP', 'Laravel', 'REST API'],
                'seo_title'       => 'OWASP Top 10 Laravel Fixes',
                'seo_description' => 'Practical OWASP Top 10 mitigations for Laravel: injection, broken auth, SSRF, and security misconfiguration.',
                'seo_keywords'    => 'owasp, laravel security, application security',
                'og_title'        => 'OWASP Fixes for Laravel',
                'og_description'  => 'Close common security gaps in Laravel applications using OWASP guidance.',
                'content'         => $this->contentOwaspLaravel(),
            ],
            [
                'slug'            => 'prompt-engineering-basics-software-teams',
                'category_slug'   => 'artificial-intelligence',
                'title'           => 'Prompt Engineering Basics for Software Teams',
                'excerpt'         => 'Effective prompts specify role, context, constraints, and output format. Teams that treat prompting as an engineering skill ship better AI-assisted features faster.',
                'tags'            => ['Prompt Engineering', 'Machine Learning'],
                'seo_title'       => 'Prompt Engineering for Developers',
                'seo_description' => 'Foundational prompt engineering techniques for software teams using LLMs in development workflows.',
                'seo_keywords'    => 'prompt engineering, llm, ai development',
                'og_title'        => 'Prompt Engineering Basics',
                'og_description'  => 'Write better LLM prompts with structure, examples, and evaluation loops.',
                'content'         => $this->contentPromptEngineering(),
            ],
            [
                'slug'            => 'structure-career-path-full-stack-development',
                'category_slug'   => 'career-guidance',
                'title'           => 'How to Structure a Career Path in Full-Stack Development',
                'excerpt'         => 'Full-stack careers are not linear. A deliberate progression across fundamentals, specialization, and leadership skills keeps growth intentional rather than accidental.',
                'tags'            => ['Soft Skills', 'System Design'],
                'seo_title'       => 'Full-Stack Developer Career Path',
                'seo_description' => 'A staged career roadmap for full-stack developers: skills, projects, and interview readiness.',
                'seo_keywords'    => 'full stack career, developer roadmap, software career',
                'og_title'        => 'Full-Stack Career Roadmap',
                'og_description'  => 'Plan your growth from junior developer to senior full-stack engineer.',
                'content'         => $this->contentCareerPath(),
            ],
            [
                'slug'            => 'system-design-interview-caching-layers',
                'category_slug'   => 'software-engineering',
                'title'           => 'System Design Interview: Caching Layers',
                'excerpt'         => 'Caching is one of the first tools interviewers expect you to reach for. Understand client, CDN, application, and database cache tiers with clear trade-offs.',
                'tags'            => ['System Design', 'Redis', 'Performance'],
                'seo_title'       => 'System Design: Caching Layers',
                'seo_description' => 'Multi-layer caching in system design interviews: CDN, Redis, application cache, and invalidation strategies.',
                'seo_keywords'    => 'system design, caching, redis, interview',
                'og_title'        => 'Caching Layers in System Design',
                'og_description'  => 'Explain caching tiers confidently in system design interviews.',
                'content'         => $this->contentCachingLayers(),
            ],
            [
                'slug'            => 'writing-tests-survive-refactors',
                'category_slug'   => 'software-engineering',
                'title'           => 'Writing Tests That Survive Refactors',
                'excerpt'         => 'Brittle tests slow teams down more than missing tests. Focus on behavior over implementation details and your suite will stay useful through architectural changes.',
                'tags'            => ['Testing', 'Laravel', 'Soft Skills'],
                'seo_title'       => 'Tests That Survive Refactors',
                'seo_description' => 'Write maintainable tests by asserting behavior, using test doubles wisely, and avoiding implementation coupling.',
                'seo_keywords'    => 'testing, refactor, unit tests, integration tests',
                'og_title'        => 'Maintainable Test Design',
                'og_description'  => 'Keep your test suite valuable across refactors and framework upgrades.',
                'content'         => $this->contentTestsRefactors(),
            ],
        ];
    }

    private function contentLaravelServiceContainer(): string
    {
        return <<<'HTML'
<h2>What the Service Container Does</h2>
<p>The Laravel service container is a dependency injection (DI) container. Instead of manually constructing objects with <code>new</code>, you declare what a class needs in its constructor and let the framework resolve dependencies automatically.</p>
<p>This matters because tightly coupled code is hard to test and expensive to change. When your mailer, repository, or payment gateway is injected, you can swap implementations in tests or configuration without rewriting business logic.</p>

<h2>Bindings and Resolution</h2>
<p>Most classes resolve through reflection: Laravel inspects constructor type hints and builds the object graph. For interfaces or third-party SDKs, you register explicit bindings in a service provider.</p>
<pre>// AppServiceProvider
$this->app-&gt;bind(PaymentGateway::class, StripeGateway::class);

// Constructor injection
public function __construct(private PaymentGateway $gateway) {}</pre>
<p>Use <code>bind()</code> when you need a new instance each time. Use <code>singleton()</code> when a single shared instance should exist for the request or application lifetime.</p>

<h3>When to Register Custom Bindings</h3>
<ul>
<li>You depend on an interface, not a concrete class.</li>
<li>A third-party class needs configuration before construction.</li>
<li>You want environment-specific implementations (sandbox vs production).</li>
</ul>

<h2>Contextual Binding</h2>
<p>Sometimes two classes need different implementations of the same interface. Contextual binding solves that without global overrides.</p>
<pre>$this-&gt;app-&gt;when(ReportExporter::class)
    -&gt;needs(StorageDriver::class)
    -&gt;give(S3StorageDriver::class);</pre>

<h2>Testing with the Container</h2>
<p>In feature tests, prefer real integrations for your own application services and mock only external boundaries. In unit tests, pass mocks directly to the class under test or override container bindings for the specific test case.</p>
<p><strong>Practical rule:</strong> if a dependency crosses a network boundary or costs money per call, mock it. If it is your own repository or action class, use the real implementation with an in-memory or sqlite database.</p>

<h2>Common Mistakes</h2>
<ul>
<li>Resolving heavy services inside loops instead of injecting once.</li>
<li>Calling <code>app()</code> helper deep inside domain logic (service location anti-pattern).</li>
<li>Registering everything as a singleton when stateful objects should be transient.</li>
</ul>
HTML;
    }

    private function contentEloquentPerformance(): string
    {
        return <<<'HTML'
<h2>Start with Query Count, Not Micro-optimizations</h2>
<p>Before tuning SQL syntax, count how many queries a page executes. Laravel Debugbar, Telescope, or <code>DB::listen()</code> make invisible N+1 problems obvious. A route that fires 120 queries will feel slow regardless of indexes.</p>

<h2>Eager Loading Relationships</h2>
<p>Accessing <code>$post-&gt;author-&gt;name</code> inside a loop triggers one query per row unless you eager load.</p>
<pre>Post::with(['author', 'tags'])-&gt;paginate(20);</pre>
<p>For nested relations, use dot notation: <code>with('comments.author')</code>. When you only need a subset of columns, combine <code>with()</code> with constrained eager loads to reduce payload size.</p>

<h3>Select Only What You Need</h3>
<ul>
<li>Avoid <code>SELECT *</code> on wide tables when listing views.</li>
<li>Use <code>select(['id', 'title', 'published_at'])</code> on the parent model.</li>
<li>Ensure foreign keys used in relationships are included in the select list.</li>
</ul>

<h2>Chunking Large Datasets</h2>
<p>Loading 50,000 models into memory will exhaust PHP memory limits. Use <code>chunkById()</code> or <code>lazy()</code> for exports, notifications, and batch updates.</p>
<pre>User::where('active', true)-&gt;chunkById(500, function ($users) {
    foreach ($users as $user) {
        // process
    }
});</pre>

<h2>Indexes That Match Filters</h2>
<p>Eloquent generates SQL, but the database still needs appropriate indexes on columns in <code>where</code>, <code>orderBy</code>, and join keys. Composite indexes should lead with the most selective column used in equality filters.</p>

<h2>Caching Expensive Aggregates</h2>
<p>Counting related records on every request is expensive. Cache dashboard metrics with TTL invalidation, or maintain counter columns updated by model events when strong consistency is not required.</p>

<h2>Profiling Checklist</h2>
<ul>
<li>Enable slow query logging in staging.</li>
<li>Compare query count before and after refactors.</li>
<li>Measure p95 latency, not just local dev timings.</li>
<li>Validate pagination uses indexed columns.</li>
</ul>
HTML;
    }

    private function contentSanctumApi(): string
    {
        return <<<'HTML'
<h2>Why Sanctum for APIs</h2>
<p>Laravel Sanctum provides a minimal authentication layer for SPAs, mobile clients, and machine-to-machine tokens. It integrates with Laravel's guard system and avoids the ceremony of full OAuth2 servers when you control both the client and API.</p>

<h2>Installation and Model Setup</h2>
<p>Publish Sanctum's migration and add the <code>HasApiTokens</code> trait to your User model. Configure token expiration in <code>config/sanctum.php</code> so compromised tokens have a bounded lifetime.</p>

<h3>Issuing Personal Access Tokens</h3>
<pre>$token = $user-&gt;createToken('mobile-app', ['posts:read'])-&gt;plainTextToken;</pre>
<p>Store only a hash in the database; the plain text token is shown once. Treat it like a password in client storage—prefer secure device keystores over localStorage when possible.</p>

<h2>Protecting Routes</h2>
<p>Apply the <code>auth:sanctum</code> middleware to API route groups. Combine with ability checks if you use token abilities for fine-grained authorization.</p>
<ul>
<li>Version your API routes (<code>/api/v1/...</code>).</li>
<li>Return consistent JSON error envelopes.</li>
<li>Rate limit authentication and write endpoints.</li>
</ul>

<h2>SPA Authentication vs Tokens</h2>
<p>First-party SPAs on the same domain can use cookie-based session authentication with Sanctum's CSRF flow. Mobile and third-party integrations should use bearer tokens. Do not mix patterns on the same route without understanding CSRF implications.</p>

<h2>Security Hardening</h2>
<p><strong>Rotate tokens</strong> on password change. <strong>Log token creation</strong> with device metadata. <strong>Reject tokens</strong> after explicit logout by deleting token records. Pair Sanctum with HTTPS everywhere and strict CORS configuration.</p>

<h2>Testing API Endpoints</h2>
<p>In Pest or PHPUnit, use <code>Sanctum::actingAs($user)</code> to authenticate without generating real tokens for every test. Assert both authorized and forbidden responses for each protected route.</p>
HTML;
    }

    private function contentPhp83(): string
    {
        return <<<'HTML'
<h2>Typed Class Constants</h2>
<p>PHP 8.3 allows typed constants in classes, interfaces, and traits. This removes ambiguous magic strings and gives static analysis tools stronger signals.</p>
<pre>class OrderStatus {
    public const string PENDING = 'pending';
    public const string SHIPPED = 'shipped';
}</pre>
<p>Enums remain the better choice for exhaustive sets, but typed constants are ideal for framework-style configuration classes.</p>

<h2>json_validate()</h2>
<p>Previously, validating JSON meant decoding and checking <code>json_last_error()</code>. PHP 8.3 adds <code>json_validate()</code>, which checks syntax without building a PHP data structure—faster for large payloads.</p>
<pre>if (! json_validate($requestBody)) {
    throw new InvalidJsonException();
}</pre>

<h2>#[\Override] Attribute</h2>
<p>The override attribute documents intent and catches silent bugs when a parent method is renamed. If the parent no longer has a matching method, PHP throws a compile-time error.</p>

<h3>Other Quality-of-Life Updates</h3>
<ul>
<li><strong>Randomizer improvements</strong> for shuffling and picking values with clearer APIs.</li>
<li><strong>Dynamic class constant fetch</strong> syntax (<code>$class::{'CONST'}</code>).</li>
<li><strong>mb_str_pad()</strong> for multibyte-safe string padding in CLI and reporting tools.</li>
</ul>

<h2>Migration Strategy</h2>
<p>Upgrade the runtime in CI first, run your test suite, then deploy. Most Laravel 10/11 applications move to 8.3 with few code changes if extensions are current. Watch for deprecated dynamic property patterns in older libraries.</p>

<h2>When Features Matter in Laravel Apps</h2>
<p>Use <code>json_validate()</code> at API boundaries. Adopt typed constants in domain value objects. Enable static analysis (PHPStan/Psalm) to capitalize on stronger typing across the framework and your code.</p>
HTML;
    }

    private function contentNodeMicroservices(): string
    {
        return <<<'HTML'
<h2>Define Service Boundaries First</h2>
<p>A microservice should own a cohesive business capability—not every npm package in your repo. Start by identifying contexts that change for different reasons: billing, notifications, catalog, identity. Each context becomes a candidate service with its own datastore.</p>

<h2>Node.js Strengths and Limits</h2>
<p>Node excels at I/O-heavy workloads: gateways, real-time fan-out, webhooks, and BFF (backend-for-frontend) layers. CPU-bound tasks (video transcoding, heavy PDF generation) should run in worker queues or dedicated services so the event loop stays responsive.</p>

<h3>Recommended Stack Patterns</h3>
<ul>
<li><strong>Express or Fastify</strong> for HTTP APIs with explicit routing modules.</li>
<li><strong>Message broker</strong> (RabbitMQ, SQS) for async integration between services.</li>
<li><strong>OpenTelemetry</strong> for distributed tracing across service calls.</li>
</ul>

<h2>Communication and Contracts</h2>
<p>Prefer versioned REST or event schemas with backward compatibility rules. Avoid sharing databases across services—that creates hidden coupling. Publish OpenAPI or AsyncAPI documents as the contract source of truth.</p>

<h2>Resilience</h2>
<p>Apply timeouts, retries with jitter, and circuit breakers on outbound HTTP calls. Fail fast when dependencies are unhealthy and serve degraded responses where business rules allow.</p>
<pre>const response = await fetch(url, { signal: AbortSignal.timeout(3000) });</pre>

<h2>Deployment and Observability</h2>
<p>Containerize each service with health and readiness probes. Log structured JSON with correlation IDs propagated from incoming requests. Dashboards should show error rate, latency percentiles, and queue depth—not only CPU graphs.</p>

<h2>When Not to Split</h2>
<p>If your team is small and domain boundaries are unclear, a modular monolith may ship faster. Extract services when independent scaling, deployment cadence, or fault isolation provide measurable value.</p>
HTML;
    }

    private function contentReactRsc(): string
    {
        return <<<'HTML'
<h2>The Problem Server Components Solve</h2>
<p>Traditional client-heavy React apps download large JavaScript bundles before showing meaningful content. Server Components render on the server and send a serialized component tree to the client, reducing bundle size and improving first paint for data-rich pages.</p>

<h2>Server vs Client Components</h2>
<p><strong>Server Components</strong> can fetch data directly, access secrets, and render HTML without shipping component logic to the browser. They cannot use hooks, browser APIs, or event handlers.</p>
<p><strong>Client Components</strong> handle interactivity: clicks, forms, animations. Mark them explicitly with <code>"use client"</code> at the top of the file in frameworks that support RSC.</p>

<h3>Composition Pattern</h3>
<ul>
<li>Fetch data in Server Components near the data source.</li>
<li>Pass serializable props to small Client Components for interactivity.</li>
<li>Keep client boundaries leaf-level rather than wrapping entire pages.</li>
</ul>

<h2>Data Fetching Implications</h2>
<p>Server Components encourage colocated data fetching without client-side waterfalls. A page can query a database or CMS on the server, render HTML, and stream the result. Cache policies belong on the server tier with explicit revalidation strategies.</p>

<h2>Performance Expectations</h2>
<p>RSC is not free—server rendering adds latency if data sources are slow. Combine with edge caching, incremental static regeneration, or partial streaming where supported. Measure Time to First Byte and interaction readiness separately.</p>

<h2>Common Pitfalls</h2>
<ul>
<li>Marking large trees as client components out of habit.</li>
<li>Passing non-serializable values (functions, class instances) across the boundary.</li>
<li>Assuming server rendering removes the need for authorization checks on mutations.</li>
</ul>

<h2>Practical Adoption Path</h2>
<p>Migrate read-heavy pages first: marketing content, dashboards, documentation. Keep highly interactive tools as Client Components until your team is comfortable with streaming and cache semantics.</p>
HTML;
    }

    private function contentVueComposition(): string
    {
        return <<<'HTML'
<h2>Why Composition API Scales</h2>
<p>Options API works well for small components, but large single-file components accumulate unrelated logic in <code>data</code>, <code>methods</code>, and <code>mounted</code> hooks. Composition API groups logic by feature, making components easier to read, test, and reuse.</p>

<h2>Extract Composables</h2>
<p>A composable is a function that encapsulates reactive state and behavior. Name composables after what they do: <code>useCart()</code>, <code>usePagination()</code>, <code>useAuth()</code>.</p>
<pre>export function usePagination(totalItems) {
  const page = ref(1);
  const pageSize = ref(20);
  const totalPages = computed(() =&gt; Math.ceil(totalItems.value / pageSize.value));
  return { page, pageSize, totalPages };
}</pre>

<h3>Composable Guidelines</h3>
<ul>
<li>Return refs and computeds explicitly; avoid hidden global state.</li>
<li>Accept dependencies as arguments for testability.</li>
<li>Document side effects (API calls, subscriptions) in the composable name or comments.</li>
</ul>

<h2>State Boundaries</h2>
<p>For app-wide state, prefer Pinia stores with typed store definitions. Keep component-local UI state inside composables or <code>ref</code>/<code>reactive</code> blocks. Do not mirror server data in global stores unless multiple distant components truly need it.</p>

<h2>Provide / Inject for Tree Context</h2>
<p>Theme settings, locale, or feature flags can flow through <code>provide</code> and <code>inject</code> without prop drilling. Use symbols as injection keys to avoid collisions in large codebases.</p>

<h2>Testing Strategy</h2>
<p>Test composables in isolation with Vue Test Utils or Vitest by calling the composable inside a setup wrapper. For components, assert rendered behavior rather than internal ref values.</p>

<h2>Folder Structure Example</h2>
<ul>
<li><code>components/</code> — presentational UI</li>
<li><code>composables/</code> — reusable logic</li>
<li><code>stores/</code> — shared application state</li>
<li><code>services/</code> — API clients and DTO mapping</li>
</ul>
HTML;
    }

    private function contentMysqlIndexing(): string
    {
        return <<<'HTML'
<h2>Read Queries Before Adding Indexes</h2>
<p>Indexes speed up reads but slow down writes and consume disk space. Start from real slow query logs, not hypothetical optimizations. Capture the queries that run at high frequency or high latency.</p>

<h2>EXPLAIN Is Your Starting Point</h2>
<p>Run <code>EXPLAIN ANALYZE</code> on representative queries. Look for <code>type: ALL</code> (full scan), high row estimates, and filesorts on large datasets.</p>
<pre>EXPLAIN ANALYZE
SELECT id, title FROM posts
WHERE organization_id = 3 AND status = 'published'
ORDER BY published_at DESC
LIMIT 20;</pre>

<h2>Composite Index Column Order</h2>
<p>MySQL uses the leftmost prefix of a composite index. Put equality filters first, then range filters, then columns used only for sorting if needed.</p>
<ul>
<li><strong>Good:</strong> <code>(organization_id, status, published_at)</code> for the query above.</li>
<li><strong>Weak:</strong> <code>(published_at, status)</code> when every query filters by <code>organization_id</code>.</li>
</ul>

<h3>Covering Indexes</h3>
<p>If all selected columns exist in the index, MySQL can satisfy the query from the index alone, avoiding table lookups. This is especially valuable for pagination lists.</p>

<h2>Avoid Low-Cardinality Indexes Alone</h2>
<p>An index on a boolean <code>is_active</code> column rarely helps unless combined with selective predicates. Cardinality estimates guide whether an index will be chosen by the optimizer.</p>

<h2>Maintenance</h2>
<p>Monitor index usage with performance schema or <code>sys.schema_unused_indexes</code>. Drop unused indexes created during experiments. Rebuild statistics after large data migrations so the optimizer chooses correct plans.</p>

<h2>Laravel Migration Example</h2>
<pre>$table-&gt;index(['organization_id', 'status', 'published_at']);</pre>
<p>Keep migration names and index purposes documented so future developers understand why each index exists.</p>
HTML;
    }

    private function contentDockerLaravel(): string
    {
        return <<<'HTML'
<h2>Goals for Local Docker Stacks</h2>
<p>Every developer should run the same PHP version, extensions, and services. Docker Compose encodes that environment in code, reducing "works on my machine" incidents during code review and QA handoff.</p>

<h2>Typical Laravel Compose Services</h2>
<ul>
<li><strong>app</strong> — PHP-FPM with required extensions (pdo_mysql, redis, intl).</li>
<li><strong>web</strong> — Nginx reverse proxy to PHP-FPM.</li>
<li><strong>mysql</strong> — primary relational database with persistent volume.</li>
<li><strong>redis</strong> — cache, session, or queue backend.</li>
<li><strong>queue</strong> — <code>php artisan queue:work</code> as a separate container.</li>
</ul>

<h3>Volume Strategy</h3>
<p>Bind-mount the project directory for live code edits. Use named volumes for database data so <code>docker compose down</code> does not wipe local seed data unintentionally.</p>

<h2>Environment Variables</h2>
<p>Keep <code>.env</code> for local secrets out of images. Reference service hostnames defined in Compose (<code>DB_HOST=mysql</code>, <code>REDIS_HOST=redis</code>). Document required variables in <code>.env.example</code>.</p>

<h2>Developer Workflow</h2>
<pre>docker compose up -d
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test</pre>
<p>Wrap common commands in Composer scripts or a Makefile so onboarding is a single documented entry point.</p>

<h2>CI/CD Alignment</h2>
<p>Reuse the same Dockerfile in CI pipelines. Build images on merge requests, run tests inside the container, and promote immutable tags to staging. Drift between local Compose and production Kubernetes manifests is reduced when the base image is shared.</p>

<h2>Production Caveats</h2>
<p>Development Compose files are not production manifests. Use secrets management, resource limits, and health checks in orchestrators like Kubernetes or ECS for real deployments.</p>
HTML;
    }

    private function contentOwaspLaravel(): string
    {
        return <<<'HTML'
<h2>Map Risks to Laravel Controls</h2>
<p>The OWASP Top 10 is a prioritization framework, not a checklist tattoo. Laravel provides strong defaults—CSRF middleware, Eloquent parameter binding, encryption services—but applications still fail when developers bypass framework protections.</p>

<h2>A01 Broken Access Control</h2>
<p>Authorize every action with policies or gates. Never rely on hidden UI elements alone. Use route model binding scoped to the authenticated user's organization.</p>
<ul>
<li>Return 404 instead of 403 when exposing resource existence is sensitive.</li>
<li>Test horizontal privilege escalation in feature tests.</li>
</ul>

<h2>A02 Cryptographic Failures</h2>
<p>Store passwords with bcrypt/argon hashing via Laravel's default user model. Force HTTPS in production. Rotate <code>APP_KEY</code> only with a documented data re-encryption plan.</p>

<h2>A03 Injection</h2>
<p>Prefer Eloquent and query builder bindings. When raw SQL is necessary, use parameter binding never string concatenation.</p>
<pre>DB::select('select * from users where email = ?', [$email]);</pre>

<h2>A05 Security Misconfiguration</h2>
<p>Disable debug mode in production. Restrict <code>/telescope</code> and <code>/horizon</code> routes. Keep dependencies updated with <code>composer audit</code> and automated security scanning.</p>

<h2>A07 Identification and Authentication Failures</h2>
<p>Enforce MFA for admin panels where possible. Rate limit login routes with <code>ThrottleRequests</code>. Invalidate sessions on password reset.</p>

<h2>A10 SSRF</h2>
<p>Validate outbound URLs from webhooks or import features. Block link-local and metadata IP ranges. Use allowlists of domains when users supply URLs to fetch.</p>

<h2>Operational Practices</h2>
<p>Run periodic OWASP ASVS-style reviews. Automate SAST in CI. Maintain an incident response runbook with logging, alerting, and key rotation steps.</p>
HTML;
    }

    private function contentPromptEngineering(): string
    {
        return <<<'HTML'
<h2>Prompting Is Interface Design</h2>
<p>Large language models respond to instructions, examples, and constraints. Prompt engineering is the practice of designing those inputs so outputs are reliable enough for production workflows—code review assistants, support drafts, and internal search.</p>

<h2>Structure High-Quality Prompts</h2>
<ul>
<li><strong>Role:</strong> who the model should act as (senior Laravel reviewer).</li>
<li><strong>Context:</strong> relevant facts, stack, and constraints.</li>
<li><strong>Task:</strong> explicit deliverable (bullet list of risks).</li>
<li><strong>Format:</strong> JSON schema, markdown sections, or word limit.</li>
</ul>

<h3>Example Skeleton</h3>
<pre>You are a senior backend engineer.
Stack: Laravel 11, MySQL, Redis queue.
Task: Review the following controller for security and performance issues.
Output: Markdown with sections Risks, Suggestions, Tests to add.</pre>

<h2>Few-Shot Examples</h2>
<p>Include one or two input/output pairs demonstrating the expected style and depth. Examples reduce format drift and help the model calibrate verbosity.</p>

<h2>Evaluation Loops</h2>
<p>Prompts decay as models update. Build a small golden set of tasks with expected properties (must mention CSRF, must cite SQL injection risk). Score outputs automatically or with human review before rolling prompt changes to all users.</p>

<h2>Safety and Privacy</h2>
<p>Strip PII from prompts sent to third-party APIs. Log prompt templates, not user secrets. Apply output filters for disallowed content in customer-facing features.</p>

<h2>Team Workflow</h2>
<p>Store prompts in version control like code. Name versions, document change rationale, and pair prompt updates with monitoring on user thumbs-down signals and task completion rates.</p>
HTML;
    }

    private function contentCareerPath(): string
    {
        return <<<'HTML'
<h2>Stages, Not Job Titles</h2>
<p>Job titles vary wildly between companies. Focus on capability stages: foundations, independent delivery, cross-team impact, and technical leadership. Your portfolio and interview performance should reflect the stage you are targeting, not only years of experience.</p>

<h2>Stage 1 — Foundations</h2>
<p>Master HTML, CSS, JavaScript, HTTP, SQL, and Git. Build small full-stack projects that include authentication, forms, validation, and deployment. Learn to read documentation and debug with browser devtools and server logs.</p>
<ul>
<li>Ship a CRUD app with tests.</li>
<li>Explain request lifecycle in your framework of choice.</li>
<li>Write clear commit messages and README files.</li>
</ul>

<h2>Stage 2 — Independent Delivery</h2>
<p>Own features end to end: API design, UI implementation, database migrations, and monitoring. Understand caching basics, background jobs, and accessibility fundamentals.</p>

<h3>Portfolio Signals</h3>
<ul>
<li>One project demonstrating API integration and error handling.</li>
<li>Evidence of code review participation.</li>
<li>Performance or security improvement you can quantify.</li>
</ul>

<h2>Stage 3 — Cross-Team Impact</h2>
<p>Lead refinements, mentor juniors, and design components or services used by multiple teams. Deepen system design skills: trade-offs, CAP considerations, idempotency, and observability.</p>

<h2>Stage 4 — Technical Leadership</h2>
<p>Define engineering standards, hiring loops, and architecture direction. Balance delivery with debt paydown. Communicate effectively with product and stakeholders about risk and timelines.</p>

<h2>Interview Preparation</h2>
<p>Alternate between coding practice, system design scenarios, and behavioral storytelling using STAR format. Keep a brag document of wins, metrics, and lessons learned—update it monthly.</p>

<h2>Sustainable Growth</h2>
<p>Specialize intentionally: frontend platform, backend APIs, or infrastructure. T-shaped skills beat resume-driven framework chasing. Community contributions, blogging, and mentoring accelerate opportunities.</p>
HTML;
    }

    private function contentCachingLayers(): string
    {
        return <<<'HTML'
<h2>Why Interviewers Ask About Caching</h2>
<p>Scaling discussions usually start with "can we cache this?" Caching moves work closer to the consumer and shields databases from repetitive reads. Strong candidates explain multiple layers and how invalidation keeps data correct.</p>

<h2>Client-Side Cache</h2>
<p>Browsers cache static assets via cache headers. Use fingerprinted filenames for JS/CSS so you can set long <code>max-age</code> safely. Avoid caching personalized HTML at the CDN unless you understand cache key segmentation.</p>

<h2>CDN Edge Cache</h2>
<p>Geographically distributed caches reduce latency for static and semi-static content. Cache keys typically include path, query parameters, and selected headers. Purge APIs invalidate objects after deployments.</p>

<h3>Good CDN Candidates</h3>
<ul>
<li>Public marketing pages.</li>
<li>Product images and downloadable assets.</li>
<li>Read-heavy API responses with explicit TTL and cache tags.</li>
</ul>

<h2>Application Cache (Redis)</h2>
<p>Store computed aggregates, session data, and rate limit counters in Redis. Serialize structured data with versioned keys so schema changes do not serve stale shapes.</p>
<pre>cache-&gt;remember('dashboard:org:42', 300, fn () =&gt; $this-&gt;buildDashboard());</pre>

<h2>Database Buffer Pool</h2>
<p>Even without Redis, databases cache hot pages in memory. Indexes keep working sets small. Do not treat the database as your only cache layer for hot keys at scale.</p>

<h2>Invalidation Strategies</h2>
<ul>
<li><strong>TTL:</strong> simple, tolerates slight staleness.</li>
<li><strong>Write-through:</strong> update cache on write, higher consistency.</li>
<li><strong>Event-driven purge:</strong> message bus triggers CDN or Redis invalidation.</li>
</ul>

<h2>Trade-offs to Mention in Interviews</h2>
<p>Caching improves read latency but adds consistency complexity. State which consistency model you accept (eventual vs strong) and how you detect cache stampedes—request coalescing, probabilistic early expiration, or single-flight locks.</p>
HTML;
    }

    private function contentTestsRefactors(): string
    {
        return <<<'HTML'
<h2>Tests Should Document Behavior</h2>
<p>A test suite is executable specification. When tests assert internal methods or private state, every refactor breaks CI even if user-visible behavior is unchanged. Write tests that describe outcomes: given inputs and interactions, what should users and callers observe?</p>

<h2>Test Pyramid Balance</h2>
<ul>
<li><strong>Unit tests</strong> for pure domain logic and edge cases.</li>
<li><strong>Integration tests</strong> for database queries, HTTP APIs, and queue dispatch.</li>
<li><strong>End-to-end tests</strong> for critical user journeys only—they are slow and flaky if overused.</li>
</ul>

<h2>Arrange-Act-Assert with Clear Names</h2>
<p>Test names should read like sentences: <code>it_denies_access_when_user_lacks_permission</code>. Keep setup minimal; extraneous fixtures obscure the behavior under test.</p>

<h3>Prefer Public APIs</h3>
<p>Call controller endpoints or service public methods instead of testing private helpers directly. If a private function is complex enough to need direct tests, extract it to a dedicated class.</p>

<h2>Test Doubles With Discipline</h2>
<p>Mocks verify collaboration; stubs return canned data. Over-mocking couples tests to call order and method names. Mock external HTTP clients and payment gateways; use real SQLite or transactional MySQL for repositories when feasible.</p>

<h2>Factories and Fixtures</h2>
<p>Use model factories with sensible defaults. Override only attributes relevant to the scenario. Large shared fixtures become mystery meat—compose data per test for clarity.</p>

<h2>Refactor Workflow</h2>
<ol>
<li>Green suite before refactor.</li>
<li>Make structural changes in small commits.</li>
<li>Run focused tests, then full CI.</li>
<li>Add characterization tests if legacy behavior is undocumented.</li>
</ol>

<h2>Signals of Healthy Tests</h2>
<p>Failures point to broken behavior, not renamed methods. Tests run in seconds locally. Flaky tests are quarantined and fixed immediately. Developers trust red builds mean real regressions.</p>
HTML;
    }
}
