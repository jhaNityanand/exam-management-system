<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Settings\UpdateSeoSettingRequest;
use App\Services\Seo\SeoSiteGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SeoSettingController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected SeoSiteGenerator $generator,
    ) {}

    public function edit(): View
    {
        $orgId = $this->currentOrgId();

        return view('backend.settings.seo', [
            'status' => $this->generator->status($orgId),
            'settings' => [
                'chunk_size' => (int) site_setting('seo_files.chunk_size', SeoSiteGenerator::DEFAULT_CHUNK),
                'robots_extra' => (string) site_setting('seo_files.robots_extra', ''),
                'humans_text' => (string) site_setting('seo_files.humans_text', ''),
                'security_contact_email' => (string) site_setting('seo_files.security_contact_email', ''),
                'security_policy_url' => (string) site_setting('seo_files.security_policy_url', ''),
                'manifest_name' => (string) site_setting('seo_files.manifest_name', site_setting('brand.site_name', 'Examtube.in')),
                'manifest_short_name' => (string) site_setting('seo_files.manifest_short_name', 'Examtube'),
                'manifest_theme_color' => (string) site_setting('seo_files.manifest_theme_color', '#0f766e'),
                'manifest_background_color' => (string) site_setting('seo_files.manifest_background_color', '#0b1220'),
            ],
        ]);
    }

    public function update(UpdateSeoSettingRequest $request): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $this->generator->updateSettings($request->validated(), $orgId);

        return response()->json([
            'success' => true,
            'message' => 'SEO settings saved.',
            'status' => $this->generator->status($orgId),
        ]);
    }

    public function regenerate(): JsonResponse
    {
        $orgId = $this->currentOrgId();
        $result = $this->generator->generate($orgId);

        return response()->json([
            'success' => true,
            'message' => 'SEO files regenerated successfully.',
            'result' => $result,
            'status' => $this->generator->status($orgId),
        ]);
    }
}
