<?php

namespace Database\Seeders\Concerns;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\Support\SeederContact;

trait ResolvesDemoContext
{
    protected function demoOrganization(): ?Organization
    {
        return Organization::query()->where('slug', 'demo-org')->first();
    }

    protected function demoEditor(): ?User
    {
        return User::query()->where('email', SeederContact::EMAIL_INFO)->first()
            ?: User::query()->where('email', SeederContact::EMAIL_ADMIN)->first();
    }
}
