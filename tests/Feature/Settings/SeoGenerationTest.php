<?php

namespace Tests\Feature\Settings;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Seo\SeoSiteGenerator;
use App\Support\OrganizationRoles;
use App\Support\UniqueUserSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SeoGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::query()->create([
            'name' => 'Examtube',
            'slug' => 'examtube',
            'status' => 'active',
        ]);

        app(SeoSiteGenerator::class)->seedDefaults($this->organization->id);
    }

    protected function tearDown(): void
    {
        foreach ([
            public_path('sitemap.xml'),
            public_path('robots.txt'),
            public_path('humans.txt'),
            public_path('manifest.json'),
            public_path('feeds/rss.xml'),
            public_path('feeds/atom.xml'),
            public_path('.well-known/security.txt'),
        ] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        if (is_dir(public_path('sitemaps'))) {
            File::deleteDirectory(public_path('sitemaps'));
        }

        parent::tearDown();
    }

    public function test_seo_generate_command_writes_public_files(): void
    {
        $this->artisan('seo:generate', ['--org' => $this->organization->id])
            ->assertSuccessful();

        $this->assertFileExists(public_path('sitemap.xml'));
        $this->assertFileExists(public_path('robots.txt'));
        $this->assertFileExists(public_path('feeds/rss.xml'));
        $this->assertFileExists(public_path('feeds/atom.xml'));
        $this->assertFileExists(public_path('humans.txt'));
        $this->assertFileExists(public_path('.well-known/security.txt'));
        $this->assertFileExists(public_path('manifest.json'));
        $this->assertFileExists(public_path('sitemaps/static.xml'));

        $robots = File::get(public_path('robots.txt'));
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Sitemap:', $robots);

        $sitemap = File::get(public_path('sitemap.xml'));
        $this->assertStringContainsString('<sitemapindex', $sitemap);
        $this->assertStringContainsString('sitemaps/static.xml', $sitemap);
    }

    public function test_admin_can_regenerate_seo_files_via_ajax(): void
    {
        $admin = User::factory()->create(['email' => 'seo-admin@example.test', 'status' => 'active']);
        UniqueUserSlug::ensureFor($admin);
        UserOrganization::query()->create([
            'user_id' => $admin->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ADMIN,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.settings.seo.regenerate'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFileExists(public_path('sitemap.xml'));
    }

    public function test_author_profile_is_public(): void
    {
        $author = User::factory()->create([
            'email' => 'author@example.test',
            'name' => 'Author Person',
            'status' => 'active',
        ]);
        UniqueUserSlug::ensureFor($author);
        UserOrganization::query()->create([
            'user_id' => $author->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ORG_ADMIN,
            'status' => 'active',
        ]);

        $this->get(route('frontend.authors.show', $author->slug))
            ->assertOk()
            ->assertSee('Author Person');
    }
}
