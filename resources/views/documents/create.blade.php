@extends('layouts.app')
@section('title', 'Upload Document')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/documents.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="doc-header">
        <div>
            <h1 class="doc-title">Upload Document</h1>
            <p class="doc-subtitle">Add a new document to the Document Center</p>
        </div>
        <a href="{{ route('documents.index') }}" class="doc-btn" style="font-size:13px;padding:7px 14px;">← Back</a>
    </div>

    @if($errors->any())
    <div style="background:#fef2f2;border:0.5px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:1rem;">
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
    @csrf

    {{-- CARD 1: Document Info --}}
    <div class="doc-form-card">
        <div class="doc-form-title">Document Information</div>

        <div class="doc-field">
            <label class="doc-label">Title *</label>
            <input type="text" name="title" class="doc-input" required
                   value="{{ old('title') }}" placeholder="e.g. Employee Handbook 2026">
        </div>

        <div class="doc-field">
            <label class="doc-label">Description</label>
            <textarea name="description" class="doc-input" rows="3"
                      placeholder="Brief description of this document..." maxlength="1000">{{ old('description') }}</textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="doc-field">
                <label class="doc-label">Category *</label>
                <select name="category" class="doc-input" required>
                    <option value="">Select category...</option>
                    @foreach($categories as $val => $label)
                    <option value="{{ $val }}" @selected(old('category') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="doc-field">
                <label class="doc-label">Version</label>
                <input type="text" name="version" class="doc-input"
                       value="{{ old('version', '1.0') }}" maxlength="20" placeholder="1.0">
            </div>
        </div>
    </div>

    {{-- CARD 2: Audience --}}
    <div class="doc-form-card">
        <div class="doc-form-title">Who can see this document?</div>

        <label class="doc-audience-option">
            <input type="radio" name="audience" value="org"
                   {{ old('audience') === 'org' ? 'checked' : '' }} style="margin-top:2px;">
            <div>
                <div style="font-size:13px;font-weight:500;color:#18181b;">Company-Wide</div>
                <div style="font-size:12px;color:#6b7280;">All employees can see this</div>
            </div>
        </label>

        <label class="doc-audience-option">
            <input type="radio" name="audience" value="department"
                   {{ old('audience') === 'department' ? 'checked' : '' }} style="margin-top:2px;">
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:500;color:#18181b;">Specific Department</div>
                <div style="font-size:12px;color:#6b7280;">Only selected department</div>
                <div id="dept-field" style="display:none;margin-top:10px;">
                    <select name="department_id" class="doc-input" style="max-width:280px;">
                        <option value="">Select department...</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </label>

        <label class="doc-audience-option">
            <input type="radio" name="audience" value="personal"
                   {{ old('audience') === 'personal' ? 'checked' : '' }} style="margin-top:2px;">
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:500;color:#18181b;">Specific Employee</div>
                <div style="font-size:12px;color:#6b7280;">Only this employee (offer letter, salary slip, etc.)</div>
                <div id="emp-field" style="display:none;margin-top:10px;">
                    <select name="employee_id" class="doc-input" style="max-width:280px;">
                        <option value="">Select employee...</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(old('employee_id') == $emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </label>
    </div>

    {{-- CARD 3: File --}}
    <div class="doc-form-card">
        <div class="doc-form-title">File</div>
        <input type="file" name="document" id="docFile" required
               accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
               onchange="showFileInfo(this)"
               style="font-size:13px;margin-bottom:8px;">
        <div style="font-size:11px;color:#9ca3af;">Max 10MB. Supported: PDF, Word, Excel, Images</div>
        <div id="file-preview" style="display:none;margin-top:10px;padding:10px 12px;background:#f9fafb;border-radius:8px;font-size:12px;color:#6b7280;"></div>
    </div>

    <div style="display:flex;gap:10px;align-items:center;">
        <button type="submit" class="doc-btn-primary">Upload Document</button>
        <a href="{{ route('documents.index') }}" class="doc-btn" style="font-size:13px;padding:8px 16px;">Cancel</a>
    </div>

    </form>

</div>

@push('scripts')
<script>
document.querySelectorAll('input[name=audience]').forEach(function(r) {
    r.addEventListener('change', function() {
        document.getElementById('dept-field').style.display =
            this.value === 'department' ? 'block' : 'none';
        document.getElementById('emp-field').style.display =
            this.value === 'personal' ? 'block' : 'none';
    });
});

// Restore on page load (old input)
var current = document.querySelector('input[name=audience]:checked');
if (current) {
    document.getElementById('dept-field').style.display = current.value === 'department' ? 'block' : 'none';
    document.getElementById('emp-field').style.display  = current.value === 'personal'   ? 'block' : 'none';
}

function showFileInfo(input) {
    var preview = document.getElementById('file-preview');
    if (input.files && input.files[0]) {
        var f    = input.files[0];
        var size = f.size < 1048576 ? Math.round(f.size / 1024) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB';
        preview.textContent = f.name + ' — ' + size;
        preview.style.display = 'block';
    }
}
</script>
@endpush
@endsection
