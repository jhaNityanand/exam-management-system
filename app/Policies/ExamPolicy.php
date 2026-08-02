<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationResources;

class ExamPolicy
{
    use AuthorizesOrganizationResources;

    public function viewAny(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function view(User $user, Exam $exam): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($exam);
    }

    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function update(User $user, Exam $exam): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($exam);
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($exam);
    }

    public function restore(User $user, Exam $exam): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($exam);
    }

    public function forceDelete(User $user, Exam $exam): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($exam);
    }

    public function publish(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }
}
