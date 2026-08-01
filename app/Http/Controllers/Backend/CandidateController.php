<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesCurrentOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Candidate\StoreCandidateRequest;
use App\Http\Requests\Backend\Candidate\UpdateCandidateRequest;
use App\Models\Exam;
use App\Models\ExamAttemptSnapshot;
use App\Services\CandidateService;
use App\Services\ProfileAvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    use ResolvesCurrentOrganization;

    public function __construct(
        protected CandidateService $service,
        protected ProfileAvatarService $avatars,
    ) {}

    public function index(): View
    {
        $exams = Exam::query()
            ->forOrg($this->currentOrgId())
            ->orderBy('title')
            ->get(['id', 'title', 'status']);

        return view('backend.candidates.index', compact('exams'));
    }

    public function create(): View
    {
        return view('backend.candidates.create', [
            'candidate' => null,
            'avatarUrl' => null,
        ]);
    }

    public function store(StoreCandidateRequest $request): RedirectResponse
    {
        $candidate = $this->service->create($request->validated(), $this->currentOrgId());

        return redirect()
            ->route('admin.candidates.show', $candidate)
            ->with('success', 'Candidate created successfully.');
    }

    public function show(int $candidate): View
    {
        $orgId = $this->currentOrgId();
        $user = $this->service->findForOrganization($candidate, $orgId, true);
        $details = $this->service->detailsPayload($user, $orgId);

        return view('backend.candidates.show', array_merge([
            'candidate' => $user,
        ], $details));
    }

    public function edit(int $candidate): View
    {
        $user = $this->service->findForOrganization($candidate, $this->currentOrgId());

        return view('backend.candidates.edit', [
            'candidate' => $user,
            'avatarUrl' => $this->avatars->url($user->profile?->avatar),
        ]);
    }

    public function update(UpdateCandidateRequest $request, int $candidate): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $user = $this->service->findForOrganization($candidate, $orgId);
        $updated = $this->service->update($user, $request->validated(), $orgId);

        return redirect()
            ->route('admin.candidates.show', $updated)
            ->with('success', 'Candidate updated successfully.');
    }

    public function destroy(int $candidate): RedirectResponse
    {
        $user = $this->service->findForOrganization($candidate, $this->currentOrgId());
        $this->service->delete($user);

        return redirect()
            ->route('admin.candidates.index')
            ->with('success', 'Candidate moved to bin.');
    }

    public function restore(int $candidate): RedirectResponse
    {
        $user = $this->service->findForOrganization($candidate, $this->currentOrgId(), true);
        abort_unless($user->trashed(), 404);
        $this->service->restore($user);

        return redirect()
            ->route('admin.candidates.show', $user)
            ->with('success', 'Candidate restored successfully.');
    }

    public function toggleStatus(int $candidate): RedirectResponse
    {
        $orgId = $this->currentOrgId();
        $user = $this->service->findForOrganization($candidate, $orgId);
        $updated = $this->service->toggleStatus($user, $orgId);

        $label = $updated->status === 'active' ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "Candidate {$label} successfully.");
    }

    public function resetPassword(Request $request, int $candidate): RedirectResponse
    {
        $request->validate([
            'password' => ['nullable', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user = $this->service->findForOrganization($candidate, $this->currentOrgId());
        $plain = $this->service->resetPassword($user, $request->input('password'));

        return redirect()
            ->back()
            ->with('success', 'Password reset successfully.')
            ->with('generated_password', $plain);
    }

    public function showSnapshot(int $candidate, ExamAttemptSnapshot $snapshot): StreamedResponse
    {
        $user = $this->service->findForOrganization($candidate, $this->currentOrgId(), true);

        return $this->service->streamSnapshot($user, $snapshot);
    }

    public function downloadSnapshot(int $candidate, ExamAttemptSnapshot $snapshot): StreamedResponse
    {
        $user = $this->service->findForOrganization($candidate, $this->currentOrgId(), true);

        return $this->service->downloadSnapshot($user, $snapshot);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $this->validatedIds($request);
        $orgId = $this->currentOrgId();

        foreach ($ids as $id) {
            $user = $this->service->findForOrganization($id, $orgId);
            $this->service->delete($user);
        }

        return redirect()
            ->route('admin.candidates.index')
            ->with('success', count($ids).' candidate(s) moved to bin.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $this->validatedIds($request);
        $orgId = $this->currentOrgId();

        foreach ($ids as $id) {
            $user = $this->service->findForOrganization($id, $orgId, true);
            if ($user->trashed()) {
                $this->service->restore($user);
            }
        }

        return redirect()
            ->route('admin.candidates.index')
            ->with('success', count($ids).' candidate(s) restored.');
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $orgId = $this->currentOrgId();

        foreach ($data['ids'] as $id) {
            $user = $this->service->findForOrganization((int) $id, $orgId);
            if ($user->status !== $data['status']) {
                $this->service->setStatus($user, $orgId, $data['status']);
            }
        }

        return redirect()
            ->route('admin.candidates.index')
            ->with('success', 'Candidate status updated.');
    }

    /**
     * @return list<int>
     */
    protected function validatedIds(Request $request): array
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        return array_values(array_unique(array_map('intval', $data['ids'])));
    }
}
