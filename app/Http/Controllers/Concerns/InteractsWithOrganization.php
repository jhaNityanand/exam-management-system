<?php

namespace App\Http\Controllers\Concerns;

trait InteractsWithOrganization
{
    protected function currentOrgId(): int
    {
        $id = current_organization_id();
        abort_if($id === null, 404);

        return $id;
    }

    protected function authorizeOrgModel(object $model, string $foreignKey = 'organization_id'): void
    {
        abort_if((int) $model->{$foreignKey} !== $this->currentOrgId(), 403);
    }

    protected function panelLayout(): string
    {
        return 'layouts.app';
    }
}
