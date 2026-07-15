<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToOrganization
{
    public function scopeForOrg(Builder $query, int $orgId): Builder
    {
        return $query->where($this->getTable().'.organization_id', $orgId);
    }
}
