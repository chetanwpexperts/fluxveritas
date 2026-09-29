@extends('layouts.app')
@section('title', $document->title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/documents.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div style="margin-bottom:1rem;">
        <a href="{{ route('documents.index') }}" style="font-size:12px;color:#9ca3af;text-decoration:none;">← Back to Documents</a>
    </div>

    @if(session('success'))
    <div style="background:#eaf3de;border:0.5px solid #c0dd97;color:#3b6d11;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:1rem;">
        {{ session('success') }}
    </div>
    @endif

    <div class="doc-show-grid">

        {{-- LEFT --}}
        <div>
            <div class="doc-show-card">
                @php
                    $ext = strtolower(pathinfo($document->file_name, PATHINFO_EXTENSION));
                    $icons = ['pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'xls' => '📊', 'xlsx' => '📊', 'png' => '🖼️', 'jpg' => '🖼️', 'jpeg' => '🖼️'];
                @endphp
                <div class="doc-show-icon">{{ $icons[$ext] ?? '📁' }}</div>
                <div class="doc-show-title">{{ $document->title }}</div>

                <div style="display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap;">
                    <span class="doc-badge doc-badge-blue">{{ $document->category_label }}</span>
                    <span class="doc-badge doc-badge-gray">v{{ $document->version }}</span>
                </div>

                @if($document->description)
                <p style="font-size:13px;color:#6b7280;margin-bottom:14px;line-height:1.5;">{{ $document->description }}</p>
                @endif

                <div style="border-top:0.5px solid #f3f4f6;padding-top:14px;">
                    <div class="doc-meta-row">
                        <span class="doc-meta-label">Audience</span>
                        <span class="doc-meta-value">
                            @if($document->audience === 'org') Company-Wide
                            @elseif($document->audience === 'department') {{ $document->department?->name ?? 'Department' }}
                            @else {{ $document->employee?->name ?? 'Employee' }}
                            @endif
                        </span>
                    </div>
                    <div class="doc-meta-row">
                        <span class="doc-meta-label">Uploaded by</span>
                        <span class="doc-meta-value">{{ $document->uploader?->name ?? '—' }}</span>
                    </div>
                    <div class="doc-meta-row">
                        <span class="doc-meta-label">Date</span>
                        <span class="doc-meta-value">{{ $document->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="doc-meta-row">
                        <span class="doc-meta-label">File size</span>
                        <span class="doc-meta-value">{{ $document->file_size_formatted }}</span>
                    </div>
                    <div class="doc-meta-row">
                        <span class="doc-meta-label">Downloads</span>
                        <span class="doc-meta-value">{{ $document->download_count }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT --}}
        <div>

            {{-- Download --}}
            <div class="doc-show-card">
                <div class="doc-form-title">Download</div>
                <a href="{{ route('documents.download', $document) }}" class="doc-btn-primary" style="width:100%;text-align:center;display:block;">
                    ↓ Download Document
                </a>
                <div style="font-size:11px;color:#9ca3af;margin-top:8px;text-align:center;">{{ $document->file_name }} · {{ $document->file_size_formatted }}</div>
            </div>

            @if($isHr)

            {{-- Update Version --}}
            <details class="doc-show-card" style="cursor:default;">
                <summary style="cursor:pointer;font-size:14px;font-weight:500;color:#18181b;list-style:none;display:flex;justify-content:space-between;align-items:center;">
                    Update Version
                    <span style="font-size:12px;color:#9ca3af;font-weight:400;">Current: v{{ $document->version }}</span>
                </summary>
                <div style="margin-top:14px;">
                    <form method="POST" action="{{ route('documents.version', $document) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="doc-field">
                            <label class="doc-label">New File</label>
                            <input type="file" name="document" required accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" style="font-size:13px;">
                        </div>
                        <div class="doc-field">
                            <label class="doc-label">Version Number</label>
                            <input type="text" name="version" class="doc-input" placeholder="e.g. 2.0" maxlength="20">
                        </div>
                        <button type="submit" class="doc-btn-primary">Update Version</button>
                    </form>
                </div>
            </details>

            {{-- Download History --}}
            <div class="doc-show-card">
                <div class="doc-form-title">Download History</div>
                @if($downloads->isEmpty())
                <div style="font-size:13px;color:#9ca3af;font-style:italic;">No downloads yet.</div>
                @else
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:6px 8px;color:#9ca3af;font-weight:500;border-bottom:0.5px solid #f3f4f6;font-size:11px;">Employee</th>
                            <th style="text-align:left;padding:6px 8px;color:#9ca3af;font-weight:500;border-bottom:0.5px solid #f3f4f6;font-size:11px;">Date</th>
                            <th style="text-align:left;padding:6px 8px;color:#9ca3af;font-weight:500;border-bottom:0.5px solid #f3f4f6;font-size:11px;">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($downloads as $dl)
                        <tr>
                            <td style="padding:7px 8px;border-bottom:0.5px solid #f9fafb;">{{ $dl->user?->name ?? '—' }}</td>
                            <td style="padding:7px 8px;border-bottom:0.5px solid #f9fafb;color:#6b7280;">{{ $dl->downloaded_at->format('d M Y') }}</td>
                            <td style="padding:7px 8px;border-bottom:0.5px solid #f9fafb;color:#9ca3af;">{{ $dl->downloaded_at->format('H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            {{-- Danger Zone --}}
            <div class="doc-show-card" style="border-color:#fecaca;">
                <div class="doc-form-title" style="color:#991b1b;">Danger Zone</div>
                <form method="POST" action="{{ route('documents.destroy', $document) }}"
                      onsubmit="return confirm('Permanently delete this document? Cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="doc-btn doc-btn-danger" style="font-size:13px;padding:7px 14px;">
                        Delete Document
                    </button>
                </form>
            </div>

            @endif

        </div>
    </div>

</div>
@endsection
