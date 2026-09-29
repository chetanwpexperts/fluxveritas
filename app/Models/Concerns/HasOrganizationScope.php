<?php

namespace App\Models\Concerns;

trait HasOrganizationScope
{
    // Laravel auto-calls boot{TraitName}() during Model::boot()
    protected static function bootHasOrganizationScope(): void
    {
        static::addGlobalScope('organization', function ($query) {
            if (auth()->check() && !auth()->user()->hasRole('super_admin')) {
                $query->where('organization_id', auth()->user()->organization_id);
            }
        });
    }
}
