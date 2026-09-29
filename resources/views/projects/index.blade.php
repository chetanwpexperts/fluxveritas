@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">
            @if(($viewMode ?? 'all') === 'managed')
                My Projects
            @elseif(($viewMode ?? 'all') === 'assigned')
                My Assigned Projects
            @else
                All Projects
            @endif
        </h1>
        <p class="page-subtitle">
            @if(($viewMode ?? 'all') === 'managed')
                Projects you manage
            @elseif(($viewMode ?? 'all') === 'assigned')
                Projects where you have assigned tasks
            @else
                All projects in your organization
            @endif
        </p>
    </div>
    @can('create_projects')
    <div class="page-header-right">
        <a href="{{ route('projects.create') }}" class="btn-primary">+ New Project</a>
    </div>
    @endcan
</div>

        @if (session('success'))
            <div class="fv-alert fv-alert-success anim-fade-up">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('warning'))
            <div class="fv-alert fv-alert-warning anim-fade-up">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ session('warning') }}
            </div>
        @endif

        <div class="fv-card anim-fade-up">
            <div class="fv-section-header">
                <div class="fv-section-title">
                    @if(($viewMode ?? 'all') === 'managed') My Projects
                    @elseif(($viewMode ?? 'all') === 'assigned') Assigned Projects
                    @else All Projects
                    @endif
                </div>
            </div>

            @if ($projects->isEmpty())
                <div class="fv-empty">
                    <div style="width:56px; height:56px; border-radius:12px; background:var(--surface); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                        <svg style="width:28px; height:28px; color:var(--text-4);" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                        </svg>
                    </div>
                    <div style="font-weight:600; font-size:0.9375rem; color:#0f172a; margin-bottom:6px;">No projects yet</div>
                    <div style="font-size:0.875rem; color:#475569; max-width:320px; margin:0 auto 20px;">Create your first project to start tracking GitHub activity and team contributions.</div>
                    @can('create_projects')
                    <a href="{{ route('projects.create') }}" class="fv-btn fv-btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        New Project
                    </a>
                    @endcan
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="fv-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Repository</th>
                                <th>Status</th>
                                <th>Activities</th>
                                <th>Created</th>
                                <th class="text-right"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($projects as $i => $project)
                                <tr class="anim-fade-up delay-{{ min($i+1,4) }}">
                                    <td>
                                        <div style="font-weight:600; color:#0f172a;">{{ $project->name }}</div>
                                        @if ($project->description)
                                            <div style="font-size:0.75rem; color:#475569; margin-top:2px; max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $project->description }}</div>
                                        @endif
                                    </td>
                                    <td style="font-family:monospace; font-size:0.8rem; color:#475569;">
                                        @if($project->github_owner && $project->github_repo)
                                            {{ $project->github_owner }}/{{ $project->github_repo }}
                                        @else
                                            <span style="color:#94a3b8;">—</span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap;">
                                        @if($project->status === 'active')
                                            <span class="fv-badge fv-badge-green">Active</span>
                                        @elseif($project->status === 'inactive')
                                            <span class="fv-badge fv-badge-gray">Inactive</span>
                                        @else
                                            <span class="fv-badge fv-badge-gray">Archived</span>
                                        @endif
                                    </td>
                                    <td style="color:var(--text); font-weight:600; font-variant-numeric:tabular-nums;">
                                        {{ number_format($project->activities_count) }}
                                    </td>
                                    <td style="color:#475569; font-size:0.8125rem; white-space:nowrap;">
                                        {{ $project->created_at->format('M j, Y') }}
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <a href="{{ route('projects.show', $project) }}" style="display:inline-flex; align-items:center; gap:4px; font-weight:600; font-size:0.8125rem; color:var(--text-2); text-decoration:none;">
                                            View
                                            <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
