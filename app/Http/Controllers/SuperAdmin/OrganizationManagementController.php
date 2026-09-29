<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrganizationStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Settings → Platform → Organizations (super_admin only, via route middleware).
 *
 * Shows admin- and billing-level information only. It deliberately never reads
 * org-private data: performance reviews, feedback, work logs, increments, or
 * personal employee profile fields (phone, skills, salary, etc.).
 */
class OrganizationManagementController extends Controller
{
    public function __construct(private OrganizationStatusService $status) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('status'), ['active', 'pending', 'suspended'], true)
            ? $request->query('status')
            : null;

        $orgs = Organization::query()
            ->withCount('users')
            ->when($filter, fn ($q) => $q->where('status', $filter))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                      ->orWhere('slug', 'like', $like)
                      ->orWhereHas('owner', fn ($o) => $o->where('email', 'like', $like))
                      ->orWhereHas('users', fn ($u) => $u->where('email', 'like', $like)
                          ->whereHas('roles', fn ($r) => $r->where('name', 'owner')));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = Organization::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('settings.organizations.index', [
            'orgs'   => $orgs,
            'owners' => $this->status->owners($orgs->getCollection()),
            'search' => $search,
            'filter' => $filter,
            'counts' => $counts,
        ]);
    }

    public function show(Organization $organization)
    {
        $org = $organization->loadCount('users');

        $members = User::where('organization_id', $org->id)
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(25, ['id', 'name', 'email', 'is_active', 'organization_id', 'created_at'])
            ->withQueryString();

        $activeMembers = User::where('organization_id', $org->id)->where('is_active', true)->count();

        // Most recent sign-in activity across the org (sessions table, database driver)
        $lastSeen = DB::table('sessions')
            ->whereIn('user_id', User::where('organization_id', $org->id)->select('id'))
            ->max('last_activity');

        $payments = Payment::with('paidBy:id,name,email')
            ->where('organization_id', $org->id)
            ->where('status', '!=', 'created')
            ->latest()
            ->take(10)
            ->get();

        return view('settings.organizations.show', [
            'org'           => $org,
            'owner'         => $this->status->owners(collect([$org]))->get($org->id),
            'members'       => $members,
            'activeMembers' => $activeMembers,
            'lastSeen'      => $lastSeen ? Carbon::createFromTimestamp($lastSeen) : null,
            'payments'      => $payments,
            'events'        => $this->accountEvents($org),
        ]);
    }

    public function suspend(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'reason.required' => 'Please give a reason for suspending this organization.',
            'reason.min'      => 'Please describe the reason in a little more detail (at least 10 characters).',
        ]);

        try {
            $this->status->suspend($organization, $request->user(), $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$organization->name} is suspended. Its members are signed out and can't log in until it is activated.");
    }

    public function activate(Request $request, Organization $organization): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            $this->status->activate($organization, $request->user(), $data['note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$organization->name} is active again. Its members can sign in.");
    }

    /**
     * Account-level timeline: status changes, payments and people joining.
     * Nothing from inside the organization's work (tasks, logs, reviews).
     */
    private function accountEvents(Organization $org)
    {
        $statusChanges = AuditLog::with('user:id,name')
            ->where('entity_type', 'organization')
            ->where('entity_id', $org->id)
            ->latest('created_at')
            ->take(15)
            ->get()
            ->map(fn (AuditLog $log) => [
                'at'     => $log->created_at,
                'type'   => $log->action === 'organization.suspended' ? 'suspended' : 'activated',
                'text'   => ($log->action === 'organization.suspended' ? 'Suspended' : 'Activated')
                            . ' by ' . ($log->user?->name ?? 'a platform admin'),
                'detail' => $log->new_values['reason'] ?? null,
            ]);

        $payments = Payment::where('organization_id', $org->id)
            ->whereIn('status', ['paid', 'refunded', 'failed'])
            ->latest()
            ->take(15)
            ->get()
            ->map(fn (Payment $p) => [
                'at'     => $p->refunded_at ?? $p->paid_at ?? $p->created_at,
                'type'   => 'payment',
                'text'   => $p->statusLabel() . ': ' . $p->description(),
                'detail' => \App\Services\BillingService::inr($p->amount),
            ]);

        $joins = User::where('organization_id', $org->id)
            ->latest()
            ->take(15)
            ->get(['id', 'name', 'created_at'])
            ->map(fn (User $u) => [
                'at'     => $u->created_at,
                'type'   => 'member',
                'text'   => "{$u->name} joined",
                'detail' => null,
            ]);

        $created = collect([[
            'at'     => $org->created_at,
            'type'   => 'created',
            'text'   => 'Organization created',
            'detail' => null,
        ]]);

        return $statusChanges->concat($payments)->concat($joins)->concat($created)
            ->filter(fn ($e) => $e['at'])
            ->sortByDesc('at')
            ->take(15)
            ->values();
    }
}
