<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RespondsWithFrontendJson;
use App\Models\Cms\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    use RespondsWithFrontendJson;

    public function index(): View
    {
        $orgId = $this->organizationId();

        $faqs = Faq::query()
            ->active()
            ->ordered()
            ->with('category')
            ->when($orgId, fn ($q) => $q->where(function ($inner) use ($orgId) {
                $inner->where('organization_id', $orgId)->orWhereNull('organization_id');
            }))
            ->get();

        return view('frontend.faqs.index', [
            'faqs' => $faqs,
        ]);
    }
}
