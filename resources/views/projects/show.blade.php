<x-app-layout>
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/projects.css') }}">
@endpush

    <x-slot name="header">
        <div class="proj-header-row">
            <div class="proj-header-left">
                <a href="{{ route('projects.index') }}" class="proj-back-btn">
                    <svg class="proj-back-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="proj-title">{{ $project->name }}</h1>
                    @if($project->github_owner && $project->github_repo)
                        <p class="proj-repo">{{ $project->github_owner }}/{{ $project->github_repo }}</p>
                    @endif
                </div>
            </div>
            <div class="proj-header-right">
                @if($project->status === 'active')
                    <span class="fv-badge fv-badge-green">Active</span>
                @elseif($project->status === 'inactive')
                    <span class="fv-badge fv-badge-gray proj-badge-inactive">Inactive</span>
                @else
                    <span class="fv-badge fv-badge-gray">Archived</span>
                @endif
                <a href="{{ route('projects.edit', $project->id) }}" class="fv-btn fv-btn-outline proj-edit-btn">
                    <svg class="proj-edit-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                    </svg>
                    Edit
                </a>
            </div>
        </div>
    </x-slot>

    <div class="proj-main">

        @if(session('success'))
            <div class="fv-alert fv-alert-success anim-fade-up">
                <svg class="fv-alert-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Stats --}}
        <div class="proj-stats-grid">
            <div class="fv-stat anim-fade-up delay-1">
                <div class="fv-stat-label">Commits</div>
                <div class="fv-stat-number">{{ number_format($stats['commits']) }}</div>
            </div>
            <div class="fv-stat anim-fade-up delay-2">
                <div class="fv-stat-label">Pull Requests</div>
                <div class="fv-stat-number proj-stat-prs">{{ number_format($stats['prs']) }}</div>
            </div>
            <div class="fv-stat anim-fade-up delay-3">
                <div class="fv-stat-label">Created</div>
                <div class="proj-stat-date">{{ $project->created_at->format('M j, Y') }}</div>
            </div>
            <div class="fv-stat anim-fade-up delay-4">
                <div class="fv-stat-label">Description</div>
                <div class="proj-stat-desc">{{ $project->description ?: '—' }}</div>
            </div>
        </div>

        {{-- Activity table --}}
        <div class="fv-card anim-fade-up">
            <div class="fv-section-header">
                <div>
                    <div class="fv-section-title">Activity</div>
                    <div class="fv-section-sub">Last 50 events for this project.</div>
                </div>
            </div>

            @if($activities->isEmpty())
                <div class="fv-empty">
                    <div class="proj-empty-icon-wrap">
                        <svg class="proj-empty-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                        </svg>
                    </div>
                    <div class="proj-empty-title">No activity yet</div>
                    <div class="proj-empty-desc">
                        Go to the <a href="{{ route('dashboard') }}" class="proj-empty-link">dashboard</a> and click Sync GitHub to load data.
                    </div>
                </div>
            @else
                <div class="proj-table-wrap">
                    <table class="fv-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Message</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $i => $activity)
                                @php
                                    $score   = $activity->complexity_score ?? $activity->quality_score ?? $activity->impact_score;
                                    $barPct  = $score !== null ? min(100, ((float)$score / 10) * 100) : 0;
                                    $message = $activity->metadata['message'] ?? $activity->metadata['title'] ?? null;
                                    $url     = $activity->metadata['url'] ?? null;
                                @endphp
                                <tr class="anim-fade-up delay-{{ min($i+1,4) }}">
                                    <td class="proj-td-date">{{ $activity->occurred_at->format('M j, Y') }}</td>
                                    <td class="proj-td-user">{{ $activity->user?->name ?? '—' }}</td>
                                    <td class="proj-td-type">
                                        @if($activity->event_type === 'commit')
                                            <span class="fv-badge fv-badge-gray">Commit</span>
                                        @elseif($activity->event_type === 'pull_request')
                                            <span class="fv-badge fv-badge-green">Pull Request</span>
                                        @else
                                            <span class="fv-badge fv-badge-gray">{{ ucfirst(str_replace('_', ' ', $activity->event_type)) }}</span>
                                        @endif
                                    </td>
                                    <td class="proj-td-msg">
                                        @if($message)
                                            @if($url)
                                                <a href="{{ $url }}" target="_blank" rel="noopener" class="proj-msg-link">{{ $message }}</a>
                                            @else
                                                <span class="proj-msg-text">{{ $message }}</span>
                                            @endif
                                        @else
                                            <span class="proj-dash">—</span>
                                        @endif
                                    </td>
                                    <td class="proj-td-score">
                                        @if($score !== null)
                                            <div class="score-bar-wrap">
                                                <div class="score-bar-track"><div class="score-bar-fill" style="width:{{ $barPct }}%;"></div></div>
                                                <span class="proj-score-num">{{ number_format((float)$score, 2) }}</span>
                                            </div>
                                        @else
                                            <span class="proj-dash">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
