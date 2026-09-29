@extends('layouts.app')
@section('title', 'Organizations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/platform-orgs.css') }}">
@endpush

@php
    $total = $counts->sum();
    $tabs  = ['' => ['All', $total], 'active' => ['Active', $counts['active'] ?? 0], 'pending' => ['Pending', $counts['pending'] ?? 0], 'suspended' => ['Suspended', $counts['suspended'] ?? 0]];
@endphp

@section('content')
<div class="page-wrapper">
    @include('settings.partials.tabs')

    <div class="po-page">
        <div class="po-head">
            <div>
                <h2 class="po-title">Organizations</h2>
                <p class="po-sub">Every organization on the platform. Suspending an organization signs its members out and blocks sign-in until it’s activated again.</p>
            </div>
        </div>

        @error('reason')
            <div class="po-alert po-alert-error" role="alert">{{ $message }}</div>
        @enderror

        <div class="po-toolbar">
            <nav class="po-filters" aria-label="Filter by status">
                @foreach($tabs as $key => [$label, $count])
                    <a href="{{ route('settings.organizations.index', array_filter(['status' => $key, 'q' => $search])) }}"
                       class="po-filter {{ ($filter ?? '') === $key ? 'is-active' : '' }}"
                       @if(($filter ?? '') === $key) aria-current="page" @endif>
                        {{ $label }}<span class="po-filter-count">{{ $count }}</span>
                    </a>
                @endforeach
            </nav>

            <form class="po-search" method="GET" action="{{ route('settings.organizations.index') }}" role="search">
                @if($filter)<input type="hidden" name="status" value="{{ $filter }}">@endif
                <input type="search" name="q" value="{{ $search }}" class="po-input"
                       placeholder="Search by name or owner email" aria-label="Search organizations">
                <button type="submit" class="po-btn po-btn-secondary">Search</button>
                @if($search !== '')
                    <a class="po-link" href="{{ route('settings.organizations.index', array_filter(['status' => $filter])) }}">Clear</a>
                @endif
            </form>
        </div>

        <div class="po-card">
            @if($orgs->isEmpty())
                <div class="po-empty">
                    @if($search !== '' || $filter)
                        No organizations match these filters.
                    @else
                        No organizations yet. They’ll appear here as soon as the first one signs up.
                    @endif
                </div>
            @else
                <div class="po-table-wrap">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th scope="col">Organization</th>
                                <th scope="col">Owner</th>
                                <th scope="col">Plan</th>
                                <th scope="col">Subscription</th>
                                <th scope="col" class="po-table-num">Users</th>
                                <th scope="col">Created</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($orgs as $org)
                            @php $owner = $owners->get($org->id); @endphp
                            <tr>
                                <td>
                                    <a class="po-name" href="{{ route('settings.organizations.show', $org) }}">{{ $org->name }}</a>
                                    <div class="po-muted">{{ $org->slug }}</div>
                                </td>
                                <td>{{ $owner?->email ?? '—' }}</td>
                                <td><span class="po-pill po-pill-plan">{{ ucfirst($org->effectivePlan()) }}</span></td>
                                <td>@include('settings.organizations._subscription', ['org' => $org])</td>
                                <td class="po-table-num">{{ $org->users_count }}</td>
                                <td>{{ $org->created_at?->format('j M Y') }}</td>
                                <td>@include('settings.organizations._status', ['org' => $org])</td>
                                <td>
                                    <div class="po-actions">
                                        <a class="po-btn po-btn-secondary" href="{{ route('settings.organizations.show', $org) }}">View</a>
                                        @include('settings.organizations._action', ['org' => $org])
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                @if($orgs->hasPages())
                    <div class="po-pagination">{{ $orgs->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>

@include('settings.organizations._dialogs')
@endsection

@push('scripts')
<script src="{{ asset('js/platform-orgs.js') }}"></script>
@endpush
