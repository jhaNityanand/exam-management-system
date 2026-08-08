<?php

namespace Tests\Feature\Settings;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Settings\CacheOptimizationService;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CacheOptimizationTest extends TestCase
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
            'email' => 'cache-admin@example.test',
            'status' => 'active',
        ]);

        UserOrganization::query()->create([
            'user_id' => $this->admin->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ADMIN,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_cache_optimization_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Cache & Optimization')
            ->assertSee('Clear Application Cache')
            ->assertSee('Regenerate Sitemap')
            ->assertSee('Import Legacy Examtube Data')
            ->assertSee('Fresh Migration &amp; Seed', false)
            ->assertSee('Run Database Seeders');
    }

    public function test_admin_can_run_cache_clear_action(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.cache.run'), ['action' => 'clear_app_cache'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.action', 'clear_app_cache');
    }

    public function test_admin_can_run_import_legacy_examtube_action(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.cache.run'), ['action' => 'import_legacy_examtube'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.action', 'import_legacy_examtube');
    }

    public function test_admin_can_clear_logs(): void
    {
        $logFile = storage_path('logs/test-cache-optimization.log');
        File::ensureDirectoryExists(dirname($logFile));
        File::put($logFile, "test log line\n");

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.cache.run'), ['action' => 'clear_logs'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFileDoesNotExist($logFile);
    }

    public function test_unknown_action_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.cache.run'), ['action' => 'rm_rf'])
            ->assertStatus(422);
    }

    public function test_service_rejects_unknown_action(): void
    {
        $result = app(CacheOptimizationService::class)->run('not-real');

        $this->assertFalse($result['success']);
        $this->assertSame(1, $result['exit_code']);
    }
}
