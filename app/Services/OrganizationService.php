<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrganizationService
{
    public function getAll(int $perPage = 20): LengthAwarePaginator
    {
        return Organization::withCount(['users', 'exams'])->latest()->paginate($perPage);
    }

    public function create(array $data): Organization
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        }
        $data['user_id'] = $data['user_id'] ?? Auth::id();
        $data['created_by'] = Auth::id();

        return Organization::create($data);
    }

    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization->fresh();
    }

    public function delete(Organization $organization): bool
    {
        return $organization->delete();
    }

    public function assignUser(Organization $organization, User $user, string $role = 'viewer'): void
    {
        $organization->users()->syncWithoutDetaching([
            $user->id => ['role' => $role],
        ]);
    }

    public function removeUser(Organization $organization, User $user): void
    {
        $organization->users()->detach($user->id);
    }

    public function getGlobalStats(): array
    {
        return [
            'total_orgs' => Organization::count(),
            'total_users' => User::count(),
            'active_orgs' => Organization::where('status', 'active')->count(),
        ];
    }
}
