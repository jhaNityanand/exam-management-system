<?php

namespace App\Services\Settings;

use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationMemberService
{
    /**
     * @param  array{search?: string, status?: string, per_page?: int}  $filters
     */
    public function paginate(?int $orgId, array $filters = []): LengthAwarePaginator
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        $perPage = max(5, min(50, (int) ($filters['per_page'] ?? 10)));

        $query = UserOrganization::query()
            ->with(['user:id,name,email,username,status'])
            ->where('organization_id', $orgId)
            ->where('role', OrganizationRoles::ORG_ADMIN)
            ->orderByDesc('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%');
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array{name: string, email: string, password?: string, status?: string}  $data
     */
    public function create(array $data, ?int $orgId): UserOrganization
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');

        return DB::transaction(function () use ($data, $orgId) {
            $email = strtolower(trim((string) $data['email']));
            $user = User::query()->where('email', $email)->first();

            if ($user) {
                $existing = UserOrganization::withTrashed()
                    ->where('organization_id', $orgId)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existing && ! $existing->trashed() && $existing->role === OrganizationRoles::ORG_ADMIN) {
                    throw ValidationException::withMessages([
                        'email' => 'This user is already a member of this organization.',
                    ]);
                }

                if ($existing && ! $existing->trashed() && $existing->role === OrganizationRoles::ADMIN) {
                    throw ValidationException::withMessages([
                        'email' => 'This account already has administrator access and cannot be invited as a member.',
                    ]);
                }

                // Existing accounts are only attached — never overwrite name/password/status.
                // Reuse the unique user+org row: restore if soft-deleted, or promote candidate → org_admin.
                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $existing->update([
                        'role' => OrganizationRoles::ORG_ADMIN,
                        'status' => ($data['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
                    ]);

                    return $existing->fresh()->load('user:id,name,email,username,status');
                }
            } else {
                if (empty($data['password'])) {
                    throw ValidationException::withMessages([
                        'password' => 'A password is required when creating a new member.',
                    ]);
                }

                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => $data['password'],
                    'status' => $data['status'] ?? 'active',
                    'email_verified_at' => now(),
                ]);
            }

            $membership = UserOrganization::query()->create([
                'user_id' => $user->id,
                'organization_id' => $orgId,
                'role' => OrganizationRoles::ORG_ADMIN,
                'status' => ($data['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
            ]);

            return $membership->load('user:id,name,email,username,status');
        });
    }

    /**
     * @param  array{name?: string, email?: string, password?: string, status?: string}  $data
     */
    public function update(UserOrganization $membership, array $data, ?int $orgId): UserOrganization
    {
        $this->assertOrg($membership, $orgId);

        return DB::transaction(function () use ($membership, $data) {
            $user = $membership->user;
            abort_if(! $user, 404, 'Member user not found.');

            $payload = [];
            if (array_key_exists('name', $data) && filled($data['name'])) {
                $payload['name'] = $data['name'];
            }
            if (array_key_exists('email', $data) && filled($data['email'])) {
                $payload['email'] = strtolower(trim((string) $data['email']));
            }

            // Only reset password / global status when this is the user's sole membership.
            $soleMembership = ! UserOrganization::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $membership->id)
                ->exists();

            if ($soleMembership && ! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }
            if ($soleMembership && array_key_exists('status', $data) && in_array($data['status'], ['active', 'inactive'], true)) {
                $payload['status'] = $data['status'];
            }

            if ($payload !== []) {
                $user->update($payload);
            }

            if (array_key_exists('status', $data) && in_array($data['status'], ['active', 'inactive'], true)) {
                $membership->update(['status' => $data['status']]);
            }

            // Keep role locked to org_admin for members managed here.
            if ($membership->role !== OrganizationRoles::ORG_ADMIN) {
                $membership->update(['role' => OrganizationRoles::ORG_ADMIN]);
            }

            return $membership->fresh()->load('user:id,name,email,username,status');
        });
    }

    public function delete(UserOrganization $membership, ?int $orgId): void
    {
        $this->assertOrg($membership, $orgId);
        $membership->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(UserOrganization $membership): array
    {
        $user = $membership->user;

        return [
            'id' => $membership->id,
            'user_id' => $membership->user_id,
            'name' => $user?->name ?? '',
            'email' => $user?->email ?? '',
            'username' => $user?->username,
            'role' => $membership->role,
            'role_label' => OrganizationRoles::label($membership->role),
            'status' => $membership->status,
            'user_status' => $user?->status,
            'updated_at' => optional($membership->updated_at)?->toIso8601String(),
        ];
    }

    protected function assertOrg(UserOrganization $membership, ?int $orgId): void
    {
        $orgId ??= current_organization_id();
        abort_if($orgId === null, 503, 'No organization found.');
        abort_if((int) $membership->organization_id !== (int) $orgId, 404);
    }
}
