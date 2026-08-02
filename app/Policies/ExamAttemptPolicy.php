<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;
use App\Policies\Concerns\AuthorizesOrganizationResources;

class ExamAttemptPolicy
{
    use AuthorizesOrganizationResources;

    /**
     * Candidate may only access their own attempt.
     */
    public function view(User $user, ExamAttempt $attempt): bool
    {
        if ((int) $attempt->user_id === (int) $user->id) {
            return true;
        }

        // Admin panel review of org attempts
        return $this->canManageContent($user)
            && $this->belongsToCurrentOrganization($attempt);
    }

    public function update(User $user, ExamAttempt $attempt): bool
    {
        return (int) $attempt->user_id === (int) $user->id;
    }

    public function submit(User $user, ExamAttempt $attempt): bool
    {
        return $this->update($user, $attempt);
    }
}
