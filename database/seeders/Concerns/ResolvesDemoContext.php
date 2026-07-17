<?php

namespace Database\Seeders\Concerns;

use App\Models\Organization;
use App\Models\User;

trait ResolvesDemoContext
{
    protected function demoOrganization(): ?Organization
    {
        return Organization::query()->where('slug', 'demo-org')->first();
    }

    protected function demoEditor(): ?User
    {
        return User::query()->where('email', 'editor@examms.test')->first();
    }
}
