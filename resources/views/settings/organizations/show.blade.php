@extends('layouts.app')
@section('title', $org->name . ' — Organizations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/platform-orgs.css') }}">
@endpush

@php $inr = fn (int $p) => \App\Services\BillingService::inr($p); @endphp

@section('content')
<div class="page-wrapper">
    @include('settings.partials.tabs')

    <div class="po-page">
        <div class="po-head">
            <div>
                <a class="po-back" href="{{ route('settings.organizations.index') }}">← All organizations</a>
                <h2 class="po-title">{{ $org->name }} @include('settings.organizations._status', ['org' => $org])</h2>
                <p class="po-sub">{{ $org->slug }} · Created {{ $org->created_at?->format('j M Y') }}</p>
            </div>
            <div class="po-actions">
                @include('settings.organizations._action', ['org' => $org])
            </div>
        </div>

        @error('reason')
            <div class="po-alert po-alert-error" role="alert">{{ $message }}</div>
        @enderror

        @if($org->isSuspended())
            <div class="po-alert po-alert-suspended" role="status">
                <strong>This organization is suspended — its members can’t sign in.</strong>
                {{ $org->suspension_reason ?: 'No reason recorded.' }}
                <div class="po-alert-meta">
                    @if($org->suspended_at)Suspended {{ $org->suspended_at->format('j M Y, g:i A') }}@endif
                    @if($org->suspendedBy) by {{ $org->suspendedBy->name }}@endif
                </div>
            </div>
        @endif

        <div class="po-grid">
            <section class="po-card" aria-labelledby="owner-title">
                <div class="po-card-head"><h3 class="po-card-title" id="owner-title">Owner</h3></div>
                <div class="po-card-body">
                    @if($owner)
                        <dl class="po-facts">
                            <dt>Name</dt><dd>{{ $owner->name }}</dd>
                            <dt>Email</dt><dd><a class="po-link" href="mailto:{{ $owner->email }}">{{ $owner->email }}</a></dd>
                        </dl>
                    @else
                        <p class="po-muted">No owner assigned.</p>
                    @endif
                </div>
            </section>

            <section class="po-card" aria-labelledby="plan-title">
                <div class="po-card-head"><h3 class="po-card-title" id="plan-title">Plan &amp; billing</h3></div>
                <div class="po-card-body">
                    <dl class="po-facts">
                        <dt>Plan</dt><dd>{{ ucfirst($org->effectivePlan()) }}</dd>
                        <dt>Subscription</dt><dd>@include('settings.organizations._subscription', ['org' => $org])</dd>
                        <dt>Billing cycle</dt><dd>{{ $org->billing_period ? ucfirst($org->billing_period) : '—' }}</dd>
                        <dt>Paid seats</dt><dd>{{ $org->seats ?? '—' }}</dd>
                    </dl>
                </div>
            </section>

            <section class="po-card" aria-labelledby="usage-title">
                <div class="po-card-head"><h3 class="po-card-title" id="usage-title">Usage</h3></div>
                <div class="po-card-body">
                    <dl class="po-facts">
                        <dt>Users</dt><dd>{{ $org->users_count }}</dd>
                        <dt>Active users</dt><dd>{{ $activeMembers }}</dd>
                        <dt>Last seen</dt><dd>{{ $lastSeen ? $lastSeen->diffForHumans() : 'No recent sign-ins' }}</dd>
                    </dl>
                </div>
            </section>
        </div>

        <section class="po-card" aria-labelledby="members-title">
            <div class="po-card-head">
                <h3 class="po-card-title" id="members-title">Users</h3>
                <span class="po-muted">{{ $members->total() }} total</span>
            </div>
            @if($members->isEmpty())
                <div class="po-empty">No users in this organization.</div>
            @else
                <div class="po-table-wrap">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Role</th>
                                <th scope="col">Account</th>
                                <th scope="col">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($members as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>{{ $member->roles->pluck('name')->map(fn ($r) => str_replace('_', ' ', $r))->implode(', ') ?: '—' }}</td>
                                <td>
                                    <span class="po-pill {{ $member->is_active ? 'po-pill-active' : 'po-pill-neutral' }}">
                                        {{ $member->is_active ? 'Active' : 'Deactivated' }}
                                    </span>
                                </td>
                                <td>{{ $member->created_at?->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($members->hasPages())
                    <div class="po-pagination">{{ $members->links() }}</div>
                @endif
            @endif
        </section>

        <section class="po-card" aria-labelledby="payments-title">
            <div class="po-card-head"><h3 class="po-card-title" id="payments-title">Recent payments</h3></div>
            @if($payments->isEmpty())
                <div class="po-empty">No payments yet.</div>
            @else
                <div class="po-table-wrap">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th scope="col">Date</th>
                                <th scope="col">Description</th>
                                <th scope="col">Paid by</th>
                                <th scope="col" class="po-table-num">Amount</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ ($payment->paid_at ?? $payment->created_at)->format('j M Y') }}</td>
                                <td>
                                    {{ $payment->description() }}
                                    @if($payment->receipt_number)<div class="po-muted">{{ $payment->receipt_number }}</div>@endif
                                </td>
                                <td>{{ $payment->paidBy?->email ?? '—' }}</td>
                                <td class="po-table-num">{{ $inr($payment->amount) }}</td>
                                <td><span class="po-pill po-pill-neutral">{{ $payment->statusLabel() }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="po-card" aria-labelledby="activity-title">
            <div class="po-card-head"><h3 class="po-card-title" id="activity-title">Recent account activity</h3></div>
            <div class="po-card-body">
                <ul class="po-timeline">
                    @foreach($events as $event)
                        <li>
                            <span class="po-dot po-dot-{{ $event['type'] }}" aria-hidden="true"></span>
                            <div>
                                {{ $event['text'] }}
                                @if($event['detail'])<div class="po-muted">{{ $event['detail'] }}</div>@endif
                            </div>
                            <time class="po-time" datetime="{{ $event['at']->toIso8601String() }}">{{ $event['at']->format('j M Y, g:i A') }}</time>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </div>
</div>

@include('settings.organizations._dialogs')
@endsection

@push('scripts')
<script src="{{ asset('js/platform-orgs.js') }}"></script>
@endpush
