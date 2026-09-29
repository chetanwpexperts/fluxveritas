@extends('layouts.app')
@section('title', 'Document Center')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/documents.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="doc-header">
        <div>
            <h1 class="doc-title">Document Center</h1>
            <p class="doc-subtitle">Access your important documents</p>
        </div>
        @if($isHr)
        <a href="{{ route('documents.create') }}" class="doc-btn-primary">+ Upload Document</a>
        @endif
    </div>

    @if(session('success'))
    <div style="background:#eaf3de;border:0.5px solid #c0dd97;color:#3b6d11;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:1rem;">
        {{ session('success') }}
    </div>
    @endif

    {{-- Stats (HR only) --}}
    @if($isHr && !empty($stats))
    <div class="doc-stats">
        <div class="doc-stat-card">
            <div class="doc-stat-num">{{ $stats['total'] }}</div>
            <div class="doc-stat-label">Total Documents</div>
        </div>
        <div class="doc-stat-card">
            <div class="doc-stat-num">{{ $stats['personal'] }}</div>
            <div class="doc-stat-label">Personal</div>
        </div>
        <div class="doc-stat-card">
            <div class="doc-stat-num">{{ $stats['department'] }}</div>
            <div class="doc-stat-label">Department</div>
        </div>
        <div class="doc-stat-card">
            <div class="doc-stat-num">{{ $stats['org'] }}</div>
            <div class="doc-stat-label">Company-Wide</div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('documents.index') }}" class="doc-filters">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search documents..." class="doc-filter-input doc-filter-wide">

        <select name="category" class="doc-filter-input">
            <option value="">All Categories</option>
            @foreach(['policy' => 'Company Policy','handbook' => 'Employee Handbook','offer_letter' => 'Offer Letter','appraisal' => 'Appraisal Letter','salary_slip' => 'Salary Slip','experience_letter' => 'Experience Letter','nda' => 'NDA','other' => 'Other'] as $val => $label)
            <option value="{{ $val }}" @selected(request('category') === $val)>{{ $label }}</option>
            @endforeach
        </select>

        @if($isHr)
        <select name="audience" class="doc-filter-input">
            <option value="">All Audiences</option>
            <option value="org"        @selected(request('audience') === 'org')>Company-Wide</option>
            <option value="department" @selected(request('audience') === 'department')>Department</option>
            <option value="personal"   @selected(request('audience') === 'personal')>Personal</option>
        </select>
        @endif

        <button type="submit" class="doc-filter-btn">Search</button>

        @if(request()->hasAny(['search', 'category', 'audience']))
        <a href="{{ route('documents.index') }}" class="doc-clear-link">Clear</a>
        @endif
    </form>

    @if($isHr)
    {{-- HR TABLE VIEW --}}
    @if($documents->isEmpty())
    <div class="doc-empty">
        <div class="doc-empty-icon">📁</div>
        <div class="doc-empty-text">No documents found.</div>
        <a href="{{ route('documents.create') }}" style="font-size:13px;color:#185fa5;margin-top:8px;display:inline-block;">+ Upload your first document</a>
    </div>
    @else
    @php
        $grouped = $documents->groupBy('audience');
        $audienceLabels = ['personal' => 'Personal Documents', 'department' => 'Department Documents', 'org' => 'Company-Wide Documents'];
        $audienceColors = ['personal' => 'doc-badge-purple', 'department' => 'doc-badge-blue', 'org' => 'doc-badge-green'];
    @endphp
    @foreach(['org', 'department', 'personal'] as $audienceKey)
    @if(isset($grouped[$audienceKey]) && $grouped[$audienceKey]->count())
    <div class="doc-section">
        <div class="doc-section-title">
            {{ $audienceLabels[$audienceKey] }}
            <span class="doc-count-badge">{{ $grouped[$audienceKey]->count() }}</span>
        </div>
        <div class="doc-table-wrap">
            <table class="doc-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Recipient</th>
                        <th>Version</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th>Downloads</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grouped[$audienceKey] as $doc)
                    <tr>
                        <td>
                            <a href="{{ route('documents.show', $doc) }}" style="font-weight:500;color:#18181b;text-decoration:none;">{{ $doc->title }}</a>
                            @if($doc->description)
                            <div style="font-size:11px;color:#9ca3af;">{{ Str::limit($doc->description, 50) }}</div>
                            @endif
                        </td>
                        <td><span class="doc-badge doc-badge-blue">{{ $doc->category_label }}</span></td>
                        <td style="font-size:12px;color:#6b7280;">
                            @if($audienceKey === 'department') {{ $doc->department?->name ?? '—' }}
                            @elseif($audienceKey === 'personal') {{ $doc->employee?->name ?? '—' }}
                            @else All Employees
                            @endif
                        </td>
                        <td style="font-size:12px;">v{{ $doc->version }}</td>
                        <td style="font-size:12px;color:#6b7280;">{{ $doc->file_size_formatted }}</td>
                        <td style="font-size:12px;color:#9ca3af;">{{ $doc->created_at->format('M d, Y') }}</td>
                        <td style="font-size:13px;">{{ $doc->download_count }}</td>
                        <td>
                            <div style="display:flex;gap:5px;align-items:center;">
                                <a href="{{ route('documents.download', $doc) }}" class="doc-btn">Download</a>
                                <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                      onsubmit="return confirm('Delete this document? Cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="doc-btn doc-btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endforeach
    <div style="padding:4px 0 8px;">{{ $documents->links() }}</div>
    @endif

    @else
    {{-- EMPLOYEE CARD VIEW --}}
    @php
        $myDocs      = $documents->filter(fn($d) => $d->audience === 'personal');
        $companyDocs = $documents->filter(fn($d) => in_array($d->audience, ['org','department']));
        $fileIcons   = ['pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'xls' => '📊', 'xlsx' => '📊', 'png' => '🖼️', 'jpg' => '🖼️', 'jpeg' => '🖼️'];
    @endphp

    @if($myDocs->count())
    <div class="doc-section">
        <div class="doc-section-title">My Documents <span class="doc-count-badge">{{ $myDocs->count() }}</span></div>
        <div class="doc-card-grid">
            @foreach($myDocs as $doc)
            @php $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)); @endphp
            <div class="doc-card" style="border-color:#e6f1fb;">
                <div class="doc-card-icon">{{ $fileIcons[$ext] ?? '📁' }}</div>
                <div class="doc-card-title">{{ $doc->title }}</div>
                <span class="doc-badge doc-badge-purple">{{ $doc->category_label }}</span>
                @if($doc->description)
                <div class="doc-card-desc">{{ $doc->description }}</div>
                @endif
                <div class="doc-card-meta">v{{ $doc->version }} · {{ $doc->file_size_formatted }}</div>
                <a href="{{ route('documents.download', $doc) }}" class="doc-btn-primary" style="margin-top:4px;text-align:center;width:100%;">Download</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($companyDocs->count())
    <div class="doc-section">
        <div class="doc-section-title">Company Documents <span class="doc-count-badge">{{ $companyDocs->count() }}</span></div>
        <div class="doc-card-grid">
            @foreach($companyDocs as $doc)
            @php $ext = strtolower(pathinfo($doc->file_name, PATHINFO_EXTENSION)); @endphp
            <div class="doc-card">
                <div class="doc-card-icon">{{ $fileIcons[$ext] ?? '📁' }}</div>
                <div class="doc-card-title">{{ $doc->title }}</div>
                <span class="doc-badge doc-badge-green">{{ $doc->category_label }}</span>
                @if($doc->description)
                <div class="doc-card-desc">{{ $doc->description }}</div>
                @endif
                <div class="doc-card-meta">v{{ $doc->version }} · {{ $doc->file_size_formatted }}</div>
                <a href="{{ route('documents.download', $doc) }}" class="doc-btn-primary" style="margin-top:4px;text-align:center;width:100%;">Download</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($documents->isEmpty())
    <div class="doc-empty">
        <div class="doc-empty-icon">📁</div>
        <div class="doc-empty-text">No documents available yet.</div>
    </div>
    @endif
    @endif

</div>
@endsection
