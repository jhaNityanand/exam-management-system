<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Controller;
use App\Models\LlmAccount;
use App\Models\LlmErrorLog;
use App\Models\SeoProcessingLog;
use App\Models\SitemapLog;
use App\Services\Llm\LlmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class LlmManagementController extends Controller
{
    public function __construct(
        protected LlmService $llmService,
    ) {}

    public function index(Request $request): View
    {
        $providerFilter = $request->query('provider');
        $statusFilter = $request->query('status');
        $search = $request->query('search');

        $query = LlmAccount::query()->orderedByProviderPriority();

        if (filled($providerFilter)) {
            $query->where('provider', $providerFilter);
        }

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $allAccounts = LlmAccount::all();

        if (filled($statusFilter)) {
            $matchingIds = $allAccounts->filter(fn (LlmAccount $acc) => $acc->statusBadge() === $statusFilter)
                ->pluck('id');
            $query->whereIn('id', $matchingIds);
        }

        $accounts = $query->paginate(15)->withQueryString();

        // Dashboard Stats
        $stats = [
            'total_providers' => 4,
            'total_accounts' => $allAccounts->count(),
            'active_accounts' => $allAccounts->filter(fn ($a) => $a->is_active && ! $a->isInCooldown())->count(),
            'cooldown_accounts' => $allAccounts->filter(fn ($a) => $a->isInCooldown())->count(),
            'failed_accounts' => $allAccounts->filter(fn ($a) => $a->statusBadge() === 'error')->count(),
            'requests_today' => $allAccounts->sum('requests_today'),
            'tokens_today' => $allAccounts->sum('tokens_today'),
        ];

        // Logs
        $errorLogs = LlmErrorLog::with('account')->latest('failed_at')->paginate(10, ['*'], 'error_page');
        $seoLogs = SeoProcessingLog::latest('run_at')->paginate(10, ['*'], 'seo_page');
        $sitemapLogs = SitemapLog::latest('run_at')->paginate(10, ['*'], 'sitemap_page');

        return view('backend.settings.llm.index', [
            'accounts' => $accounts,
            'stats' => $stats,
            'errorLogs' => $errorLogs,
            'seoLogs' => $seoLogs,
            'sitemapLogs' => $sitemapLogs,
            'filters' => [
                'provider' => $providerFilter,
                'status' => $statusFilter,
                'search' => $search,
            ],
            'supportedProviders' => [
                'mistral' => 'Mistral AI',
                'groq' => 'Groq',
                'gemini' => 'Google Gemini',
                'openrouter' => 'OpenRouter',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:mistral,groq,gemini,openrouter',
            'account_name' => 'required|string|max:255',
            'api_key' => 'required|string',
            'model' => 'required|string|max:255',
            'base_url' => 'nullable|string|max:255',
            'organization_id' => 'nullable|string|max:255',
            'priority' => 'required|integer|min:1',
            'daily_request_limit' => 'nullable|integer|min:1',
            'daily_token_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $account = LlmAccount::create($validated);

        return response()->json([
            'success' => true,
            'message' => "LLM Account [{$account->account_name}] created successfully.",
            'account' => $account,
        ]);
    }

    public function update(Request $request, LlmAccount $account): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:mistral,groq,gemini,openrouter',
            'account_name' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'model' => 'required|string|max:255',
            'base_url' => 'nullable|string|max:255',
            'organization_id' => 'nullable|string|max:255',
            'priority' => 'required|integer|min:1',
            'daily_request_limit' => 'nullable|integer|min:1',
            'daily_token_limit' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $account->update($validated);

        return response()->json([
            'success' => true,
            'message' => "LLM Account [{$account->account_name}] updated successfully.",
            'account' => $account->fresh(),
        ]);
    }

    public function destroy(LlmAccount $account): JsonResponse
    {
        $name = $account->account_name;
        $account->delete();

        return response()->json([
            'success' => true,
            'message' => "LLM Account [{$name}] deleted successfully.",
        ]);
    }

    public function toggleStatus(LlmAccount $account): JsonResponse
    {
        $account->update(['is_active' => ! $account->is_active]);

        $statusText = $account->is_active ? 'enabled' : 'disabled';

        return response()->json([
            'success' => true,
            'message' => "Account [{$account->account_name}] is now {$statusText}.",
            'is_active' => $account->is_active,
        ]);
    }

    public function resetCooldown(LlmAccount $account): JsonResponse
    {
        $account->update([
            'cooldown_until' => null,
            'error_count' => 0,
            'last_error_message' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Cooldown reset for [{$account->account_name}]. Account is now active.",
        ]);
    }

    public function testConnection(LlmAccount $account): JsonResponse
    {
        try {
            $provider = $this->llmService->makeProvider($account);
            $start = microtime(true);

            $response = $provider->chat(
                'You are an API connection validator. Respond ONLY with valid JSON.',
                'Respond with {"status": "ok", "message": "Connection test successful."}',
                ['operation' => 'testConnection']
            );

            $elapsedMs = round((microtime(true) - $start) * 1000, 2);

            return response()->json([
                'success' => true,
                'message' => "Connection successful ({$elapsedMs} ms)!",
                'provider' => $account->provider,
                'model' => $response->model ?? $account->model,
                'response' => $response->content,
                'tokens' => $response->totalTokens,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ], 422);
        }
    }
}
