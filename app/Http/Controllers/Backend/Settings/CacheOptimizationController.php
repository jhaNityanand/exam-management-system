<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Controller;
use App\Services\Settings\CacheOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CacheOptimizationController extends Controller
{
    public function __construct(
        protected CacheOptimizationService $optimizer,
    ) {}

    public function edit(): View
    {
        return view('backend.settings.cache', [
            'actions' => $this->optimizer->catalog(),
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(array_keys(CacheOptimizationService::ACTIONS))],
        ]);

        $result = $this->optimizer->run($data['action']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? $result['label'].' completed successfully.'
                : $result['label'].' failed.',
            'result' => $result,
        ], $result['success'] ? 200 : 422);
    }
}
