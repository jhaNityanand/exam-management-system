<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationResources;

class QuestionPolicy
{
    use AuthorizesOrganizationResources;

    public function viewAny(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function view(User $user, Question $question): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($question);
    }

    public function create(User $user): bool
    {
        return $this->canManageContent($user);
    }

    public function update(User $user, Question $question): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($question);
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($question);
    }

    public function restore(User $user, Question $question): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($question);
    }

    public function forceDelete(User $user, Question $question): bool
    {
        return $this->canManageContent($user) && $this->belongsToCurrentOrganization($question);
    }
}
