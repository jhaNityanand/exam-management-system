<?php

namespace Tests\Feature\Settings;

use App\Models\Cms\AdPlacement;
use App\Models\Cms\Advertisement;
use App\Models\Cms\GoogleAdvertisement;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Advertisement\AdvertisementService;
use App\Support\AdvertisementCatalog;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisementManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::query()->create([
            'name' => 'Examtube',
            'slug' => 'examtube',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'ads-admin@example.test',
            'status' => 'active',
        ]);

        UserOrganization::query()->create([
            'user_id' => $this->admin->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ADMIN,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_advertisements_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.advertisements.index'))
            ->assertOk()
            ->assertSee('Advertisement management')
            ->assertSee('Ads Placement')
            ->assertSee('Custom Code')
            ->assertSee('Help &amp; documentation', false);
    }

    public function test_admin_can_create_html_advertisement(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.store'), [
                'name' => 'HTML promo',
                'title' => 'Sponsored card',
                'type' => AdvertisementCatalog::TYPE_HTML,
                'html_code' => '<div class="test-ad">Sponsored</div>',
                'css_code' => '.test-ad{padding:1rem}',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('advertisements', [
            'organization_id' => $this->organization->id,
            'name' => 'HTML promo',
            'type' => AdvertisementCatalog::TYPE_HTML,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_google_ad_and_placement(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.google.store'), [
                'name' => 'Sidebar AdSense',
                'code' => '<ins class="adsbygoogle"></ins>',
                'ad_client' => 'ca-pub-123',
                'ad_slot' => '456',
                'status' => 'active',
            ])
            ->assertCreated();

        $google = GoogleAdvertisement::query()->forOrg($this->organization->id)->first();
        $this->assertNotNull($google);

        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.placements.store'), [
                'page_key' => 'home',
                'position_key' => 'after_hero',
                'source_type' => AdvertisementCatalog::SOURCE_GOOGLE,
                'google_advertisement_id' => $google->id,
                'is_enabled' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('ad_placements', [
            'organization_id' => $this->organization->id,
            'page_key' => 'home',
            'position_key' => 'after_hero',
            'source_type' => AdvertisementCatalog::SOURCE_GOOGLE,
            'google_advertisement_id' => $google->id,
            'is_enabled' => 1,
        ]);
    }

    public function test_admin_can_save_custom_code(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.advertisements.custom-code'), [
                'header_code' => '<script>/* header */</script>',
                'footer_code' => '<script>/* footer */</script>',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('custom_code.header_code', '<script>/* header */</script>');

        $code = app(AdvertisementService::class)->customCode($this->organization->id);
        $this->assertSame('<script>/* header */</script>', $code['header_code']);
        $this->assertSame('<script>/* footer */</script>', $code['footer_code']);
    }

    public function test_single_slot_rejects_second_placement(): void
    {
        $ad = Advertisement::query()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Title banner',
            'title' => 'Title banner',
            'type' => AdvertisementCatalog::TYPE_HTML,
            'html_code' => '<div>Ad</div>',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        AdPlacement::query()->create([
            'organization_id' => $this->organization->id,
            'page_key' => 'exam_detail',
            'position_key' => 'below_title',
            'source_type' => AdvertisementCatalog::SOURCE_CUSTOM,
            'advertisement_id' => $ad->id,
            'sort_order' => 1,
            'is_enabled' => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.placements.store'), [
                'page_key' => 'exam_detail',
                'position_key' => 'below_title',
                'source_type' => AdvertisementCatalog::SOURCE_CUSTOM,
                'advertisement_id' => $ad->id,
            ])
            ->assertStatus(422);
    }

    public function test_seed_defaults_creates_google_ads_and_placements(): void
    {
        app(AdvertisementService::class)->seedDefaults($this->organization->id, true);

        $this->assertSame(0, Advertisement::query()->forOrg($this->organization->id)->count());
        $this->assertSame(3, GoogleAdvertisement::query()->forOrg($this->organization->id)->count());
        $this->assertGreaterThan(0, AdPlacement::query()->forOrg($this->organization->id)->count());

        foreach (AdvertisementCatalog::pageKeys() as $pageKey) {
            $this->assertTrue(
                AdPlacement::query()->forOrg($this->organization->id)->where('page_key', $pageKey)->exists(),
                "Expected seeded placements for {$pageKey}"
            );
        }

        foreach (['about_us', 'contact_us', 'privacy_policy', 'terms', 'help_center', 'faqs', 'author_detail'] as $pageKey) {
            $this->assertTrue(
                AdPlacement::query()->forOrg($this->organization->id)->where('page_key', $pageKey)->exists(),
                "Expected seeded placements for static/browse page {$pageKey}"
            );
        }

        foreach (['blog_detail', 'news_detail'] as $pageKey) {
            $this->assertTrue(
                AdPlacement::query()
                    ->forOrg($this->organization->id)
                    ->where('page_key', $pageKey)
                    ->where('position_key', 'before_h2')
                    ->exists(),
                "Expected before-H2 placement for {$pageKey}"
            );
        }

        $this->assertTrue(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('page_key', 'exam_attempt')
                ->whereIn('position_key', ['right_after_palette', 'below_content'])
                ->exists()
        );

        $examDetailSidebar = AdvertisementCatalog::page('exam_detail')['sidebar_blocks'] ?? [];
        $this->assertNotEmpty($examDetailSidebar);
        foreach ($examDetailSidebar as $block) {
            $this->assertTrue(
                AdPlacement::query()
                    ->forOrg($this->organization->id)
                    ->where('page_key', 'exam_detail')
                    ->where('position_key', $block['after'])
                    ->exists(),
                "Expected default sidebar ad after {$block['label']} on exam_detail"
            );
        }
        $this->assertFalse(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('position_key', 'left_sidebar')
                ->exists()
        );
        $this->assertTrue(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('page_key', 'faqs')
                ->where('position_key', 'left_after_search')
                ->exists()
        );

        $horizontal = GoogleAdvertisement::query()->forOrg($this->organization->id)->where('ad_slot', '8279166266')->firstOrFail();
        $vertical = GoogleAdvertisement::query()->forOrg($this->organization->id)->where('ad_slot', '9013663436')->firstOrFail();

        $this->assertTrue(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('page_key', 'exam_list')
                ->where('position_key', 'after_header')
                ->where('google_advertisement_id', $horizontal->id)
                ->exists(),
            'Content sections should seed the horizontal Google unit'
        );
        $this->assertTrue(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('page_key', 'exam_list')
                ->where('position_key', 'right_after_categories')
                ->where('google_advertisement_id', $vertical->id)
                ->exists(),
            'Sidebar sections should seed the vertical Google unit'
        );

        $code = app(AdvertisementService::class)->customCode($this->organization->id);
        $this->assertStringContainsString('G-35TPDL6YPR', $code['header_code']);
        $this->assertStringContainsString('ca-pub-3495821309562824', $code['header_code']);
        $this->assertStringContainsString('a970034f682bb9bbc89a7eb02ee49cfe', $code['header_code']);
        $this->assertSame('', $code['footer_code']);

        $this->assertStringContainsString('data-ad-slot="9013663436"', $vertical->code);
        $this->assertStringContainsString('ca-pub-3495821309562824', $vertical->code);
    }

    public function test_catalog_includes_static_pages_and_article_heading_slots(): void
    {
        $pages = AdvertisementCatalog::pages();

        foreach (['about_us', 'contact_us', 'privacy_policy', 'terms', 'help_center', 'faqs', 'author_detail'] as $key) {
            $this->assertArrayHasKey($key, $pages);
            $this->assertNotEmpty($pages[$key]['positions']);
            $this->assertNotEmpty($pages[$key]['layout_blocks']);
        }

        foreach (['blog_detail', 'news_detail'] as $key) {
            $this->assertNotContains('above_title', $pages[$key]['positions']);
            $this->assertContains('before_h2', $pages[$key]['positions']);
            $this->assertTrue(AdvertisementCatalog::allowsMultiple('before_h2'));
        }

        $rightOnly = [
            'exam_list', 'question_list', 'blog_list', 'news_list',
            'exam_detail', 'exam_rules', 'exam_prepare', 'exam_attempt',
            'question_detail', 'blog_detail', 'news_detail',
            'category_detail', 'contact_us',
        ];
        $leftOnly = ['faqs', 'privacy_policy', 'terms', 'account'];
        $noSide = [
            'home', 'exam_result', 'question_categories', 'categories', 'authors',
            'author_detail', 'about_us', 'help_center', 'search', 'sitemap', 'cms_page',
        ];

        foreach ($pages as $key => $page) {
            $this->assertNotContains('right_top', $page['positions']);
            $this->assertNotContains('left_sidebar', $page['positions']);
            $this->assertNotContains('right_sidebar', $page['positions']);

            if (str_starts_with($key, 'error_')) {
                $this->assertSame([], $page['sidebars']);
                $this->assertSame([], $page['sidebar_blocks']);
                continue;
            }

            if (in_array($key, $rightOnly, true)) {
                $this->assertSame(['right'], $page['sidebars'], "Expected right sidebar on {$key}");
                $this->assertNotEmpty($page['sidebar_blocks'], "Expected right sections on {$key}");
            } elseif (in_array($key, $leftOnly, true)) {
                $this->assertSame(['left'], $page['sidebars'], "Expected left sidebar on {$key}");
                $this->assertNotEmpty($page['sidebar_blocks'], "Expected left sections on {$key}");
            } elseif (in_array($key, $noSide, true)) {
                $this->assertSame([], $page['sidebars'], "Expected no content sidebar on {$key}");
                $this->assertSame([], $page['sidebar_blocks'], "Expected no sidebar sections on {$key}");
            }

            if (in_array($key, ['exam_list', 'question_list', 'blog_list', 'news_list'], true)) {
                $this->assertCount(1, $page['sidebar_blocks']);
                $this->assertSame('Categories', $page['sidebar_blocks'][0]['label']);
            }

            foreach ($page['sidebar_blocks'] as $block) {
                $this->assertArrayHasKey('after', $block);
                $this->assertContains($block['after'], $page['positions'], "Missing sidebar insert {$block['after']} on {$key}");
                $this->assertTrue(AdvertisementCatalog::allowsMultiple($block['after']));
            }
        }

        $grouped = AdvertisementCatalog::pagesGrouped();
        $this->assertArrayHasKey('Pages', $grouped);
        $this->assertArrayHasKey('about_us', $grouped['Pages']);
        $this->assertArrayHasKey('contact_us', $grouped['Pages']);
        $this->assertArrayHasKey('privacy_policy', $grouped['Pages']);
    }

    public function test_default_seed_is_repeatable_and_retires_legacy_right_top_slot(): void
    {
        $service = app(AdvertisementService::class);
        $service->seedDefaults($this->organization->id, true);

        $google = GoogleAdvertisement::query()->forOrg($this->organization->id)->firstOrFail();
        AdPlacement::query()->create([
            'organization_id' => $this->organization->id,
            'page_key' => 'home',
            'position_key' => 'right_top',
            'source_type' => AdvertisementCatalog::SOURCE_GOOGLE,
            'google_advertisement_id' => $google->id,
            'sort_order' => 1,
            'is_enabled' => true,
        ]);

        $service->seedDefaults($this->organization->id);
        $this->assertFalse(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('position_key', 'right_top')
                ->exists()
        );

        $service->seedDefaults($this->organization->id, true);
        $this->assertFalse(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('position_key', 'right_top')
                ->exists()
        );
        $this->assertTrue(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('page_key', 'home')
                ->where('position_key', 'after_header')
                ->exists()
        );
        $this->assertTrue(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('page_key', 'exam_list')
                ->where('position_key', 'right_after_categories')
                ->exists()
        );
    }

    public function test_admin_can_place_before_h2_on_blog_detail(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.google.store'), [
                'name' => 'In-article H2',
                'code' => '<ins class="adsbygoogle" data-ad-slot="5461431234"></ins>',
                'ad_client' => 'ca-pub-123',
                'ad_slot' => '5461431234',
                'status' => 'active',
            ])
            ->assertCreated();

        $google = GoogleAdvertisement::query()->forOrg($this->organization->id)->first();
        $this->assertNotNull($google);

        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.placements.store'), [
                'page_key' => 'blog_detail',
                'position_key' => 'before_h2',
                'source_type' => AdvertisementCatalog::SOURCE_GOOGLE,
                'google_advertisement_id' => $google->id,
                'is_enabled' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('ad_placements', [
            'organization_id' => $this->organization->id,
            'page_key' => 'blog_detail',
            'position_key' => 'before_h2',
            'source_type' => AdvertisementCatalog::SOURCE_GOOGLE,
            'google_advertisement_id' => $google->id,
            'is_enabled' => 1,
        ]);
    }

    public function test_frontend_ad_slot_renders_enabled_google_placement(): void
    {
        app(AdvertisementService::class)->seedDefaults($this->organization->id, true);

        $html = app(AdvertisementService::class)->renderSlot('exam_list', 'right_after_categories', $this->organization->id);
        $this->assertStringContainsString('et-ad', $html);
        $this->assertStringContainsString('adsbygoogle', $html);
        $this->assertStringContainsString('9013663436', $html);
        $this->assertStringContainsString('ca-pub-3495821309562824', $html);

        $blogH2 = app(AdvertisementService::class)->renderSlot('blog_detail', 'before_h2', $this->organization->id);
        $this->assertStringContainsString('et-ad', $blogH2);
        $this->assertStringContainsString('8279166266', $blogH2);
    }
}
