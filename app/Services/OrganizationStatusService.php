<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Suspending and re-activating organizations. Every change goes through here so
 * it is written to the audit log. Users' own is_active flags are never touched:
 * access is blocked by EnsureOrganizationAccess while the org is suspended, so
 * re-activating restores exactly the members who had access before.
 */
class OrganizationStatusService
{
    public function suspend(Organization $org, User $by, string $reason): Organization
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required to suspend an organization.');
        }
        if ($org->isSuspended()) {
            throw new InvalidArgumentException("{$org->name} is already suspended.");
        }

        return DB::transaction(function () use ($org, $by, $reason) {
            $before = $org->status;

            $org->update([
                'status'            => Organization::STATUS_SUSPENDED,
                'suspended_at'      => now(),
                'suspended_by'      => $by->id,
                'suspension_reason' => $reason,
            ]);

            $this->audit($org, $by, 'organization.suspended', $before, ['reason' => $reason]);

            return $org;
        });
    }

    public function activate(Organization $org, User $by, ?string $note = null): Organization
    {
        if (!$org->isSuspended()) {
            throw new InvalidArgumentException("{$org->name} is not suspended.");
        }

        return DB::transaction(function () use ($org, $by, $note) {
            $previousReason = $org->suspension_reason;

            $org->update([
                'status'            => Organization::STATUS_ACTIVE,
                'suspended_at'      => null,
                'suspended_by'      => null,
                'suspension_reason' => null,
            ]);

            $this->audit($org, $by, 'organization.activated', Organization::STATUS_SUSPENDED, [
                'reason'                    => $note ? trim($note) : null,
                'previous_suspension_reason' => $previousReason,
            ]);

            return $org;
        });
    }

    /**
     * Owner of each organization, keyed by org id: owner_id when set, otherwise
     * the org's user with the Spatie "owner" role. Two queries for any number of orgs.
     */
    public function owners(Collection $orgs): Collection
    {
        $byId = User::whereIn('id', $orgs->pluck('owner_id')->filter())->get(['id', 'name', 'email'])->keyBy('id');

        $missing = $orgs->whereNull('owner_id')->pluck('id');
        $byRole  = $missing->isEmpty() ? collect() : User::whereIn('organization_id', $missing)
            ->whereHas('roles', fn ($r) => $r->where('name', 'owner'))
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'organization_id'])
            ->unique('organization_id')
            ->keyBy('organization_id');

        return $orgs->mapWithKeys(fn (Organization $org) => [
            $org->id => $org->owner_id ? $byId->get($org->owner_id) : $byRole->get($org->id),
        ]);
    }

    private function audit(Organization $org, User $by, string $action, ?string $before, array $details): void
    {
        AuditLog::create([
            'organization_id' => $org->id,
            'user_id'         => $by->id,
            'action'          => $action,
            'entity_type'     => 'organization',
            'entity_id'       => $org->id,
            'old_values'      => ['status' => $before],
            'new_values'      => array_merge(['status' => $org->status], array_filter($details, fn ($v) => $v !== null)),
            'ip_address'      => request()?->ip(),
            'user_agent'      => request()?->userAgent(),
        ]);
    }
}
