<?php

namespace Tests\Feature\Settings;

use App\Models\Cms\SiteSetting;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Settings\EmailConfigurationService;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailConfigurationTest extends TestCase
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
            'email' => 'email-admin@example.test',
            'status' => 'active',
        ]);

        UserOrganization::query()->create([
            'user_id' => $this->admin->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ADMIN,
            'status' => 'active',
        ]);

        app(EmailConfigurationService::class)->seedDefaults($this->organization->id, [
            'from_address' => 'hello@examtube.in',
            'from_name' => 'Examtube.in',
        ]);
    }

    public function test_admin_can_view_email_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.email'))
            ->assertOk()
            ->assertSee('Email Configuration')
            ->assertSee('Send test email')
            ->assertSee('Google OAuth');
    }

    public function test_admin_can_update_smtp_settings(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.email.update'), [
                'mailer' => 'smtp',
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'mailer@examtube.in',
                'password' => 'secret-pass',
                'encryption' => 'tls',
                'from_address' => 'noreply@examtube.in',
                'from_name' => 'Examtube Mail',
                'google_oauth_enabled' => false,
                'google_client_id' => '',
                'google_client_secret' => '',
                'google_redirect_uri' => 'http://localhost/auth/google/callback',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('settings.mailer', 'smtp')
            ->assertJsonPath('settings.host', 'smtp.example.com')
            ->assertJsonPath('settings.has_smtp_password', true);

        $this->assertDatabaseHas('site_settings', [
            'organization_id' => $this->organization->id,
            'group' => 'email',
            'key' => 'host',
            'value' => 'smtp.example.com',
        ]);

        // Password must not be stored in plaintext
        $passwordRow = SiteSetting::query()
            ->where('organization_id', $this->organization->id)
            ->where('group', 'email')
            ->where('key', 'password')
            ->first();

        $this->assertNotNull($passwordRow);
        $this->assertNotSame('secret-pass', $passwordRow->value);
    }

    public function test_blank_password_keeps_existing_secret(): void
    {
        $service = app(EmailConfigurationService::class);

        $service->update([
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user',
            'password' => 'keep-me',
            'encryption' => 'tls',
            'from_address' => 'hello@examtube.in',
            'from_name' => 'Examtube',
            'google_oauth_enabled' => false,
        ], $this->organization->id);

        $service->update([
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user',
            'password' => '',
            'encryption' => 'tls',
            'from_address' => 'hello@examtube.in',
            'from_name' => 'Examtube',
            'google_oauth_enabled' => false,
        ], $this->organization->id);

        $runtime = $service->getForRuntime($this->organization->id);
        $this->assertSame('keep-me', $runtime['password']);
    }

    public function test_admin_can_send_test_email_via_log_mailer(): void
    {
        Mail::fake();

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.email.test'), [
                'to' => 'candidate@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(\App\Mail\SettingsTestMail::class, function ($mail) {
            return $mail->hasTo('candidate@example.test');
        });
    }

    public function test_apply_to_config_sets_smtp_runtime_values(): void
    {
        $service = app(EmailConfigurationService::class);
        $service->update([
            'mailer' => 'smtp',
            'host' => 'mail.examtube.in',
            'port' => 465,
            'username' => 'smtp-user',
            'password' => 'smtp-pass',
            'encryption' => 'ssl',
            'from_address' => 'noreply@examtube.in',
            'from_name' => 'Examtube',
            'google_oauth_enabled' => true,
            'google_client_id' => 'client-123.apps.googleusercontent.com',
            'google_client_secret' => 'gsecret',
            'google_redirect_uri' => 'https://examtube.in/auth/google/callback',
        ], $this->organization->id);

        $service->applyToConfig($this->organization->id);

        $this->assertSame('smtp', Config::get('mail.default'));
        $this->assertSame('mail.examtube.in', Config::get('mail.mailers.smtp.host'));
        $this->assertSame(465, Config::get('mail.mailers.smtp.port'));
        $this->assertSame('smtp-pass', Config::get('mail.mailers.smtp.password'));
        $this->assertSame('noreply@examtube.in', Config::get('mail.from.address'));
        $this->assertSame('client-123.apps.googleusercontent.com', Config::get('services.google.client_id'));
        $this->assertTrue((bool) Config::get('services.google.enabled'));
    }

    public function test_smtp_requires_host(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.email.update'), [
                'mailer' => 'smtp',
                'host' => '',
                'port' => 587,
                'encryption' => 'tls',
                'from_address' => 'hello@examtube.in',
                'from_name' => 'Examtube',
                'google_oauth_enabled' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['host']);
    }
}
