@extends('layouts.app')
@section('title', 'Give Feedback — ' . $employee->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/feedback.css') }}">
@endpush

@section('content')
<div class="page-wrapper">

    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">Performance Feedback</h1>
            <p class="page-subtitle">
                {{ $employee->name }} &middot; {{ $employee->job_title ?? 'Team Member' }} &middot; {{ $currentPeriod }} {{ $currentYear }}
            </p>
        </div>
        <div class="page-header-right">
            <a href="{{ route('feedback.index') }}" class="btn-secondary">&larr; Back</a>
        </div>
    </div>

    @if($errors->any())
    <div class="fv-alert fv-alert-error">
        <ul class="feedback-error-list">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="feedback-info-banner">
        <span class="feedback-info-icon">ℹ️</span>
        <span>This feedback is based on <strong>observable work only</strong>. The AI system has already calculated objective scores. Your feedback adds context — it cannot change the AI score directly.</span>
    </div>

    <form method="POST" action="{{ route('feedback.store') }}">
        @csrf
        <input type="hidden" name="employee_id" value="{{ $employee->id }}">

        {{-- SECTION A: DELIVERY --}}
        <div class="feedback-section">
            <h3 class="feedback-section-title">Section A — Delivery Assessment</h3>
            <p class="feedback-section-desc">Answer based on what you observed. These are binary assessments — no scores.</p>

            <div class="feedback-question-grid">

                <div class="feedback-question">
                    <label class="feedback-question-label">
                        Did {{ $employee->name }} complete assigned tasks this period?
                    </label>
                    <div class="feedback-radio-group">
                        @foreach([1 => 'All completed', 2 => 'Most completed', 3 => 'Partially completed', 4 => 'Few completed'] as $val => $label)
                        <label class="feedback-radio-label">
                            <input type="radio" name="delivery_score" value="{{ $val }}" class="feedback-radio"
                                {{ (old('delivery_score', $existing?->delivery_score ?? 2) == $val) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('delivery_score')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="feedback-question">
                    <label class="feedback-question-label">Were deliverables submitted on time?</label>
                    <div class="feedback-radio-group">
                        @foreach([1 => 'Always on time', 2 => 'Usually on time', 3 => 'Sometimes delayed', 4 => 'Often delayed'] as $val => $label)
                        <label class="feedback-radio-label">
                            <input type="radio" name="timeliness_score" value="{{ $val }}" class="feedback-radio"
                                {{ (old('timeliness_score', $existing?->timeliness_score ?? 2) == $val) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('timeliness_score')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="feedback-question">
                    <label class="feedback-question-label">
                        Was {{ $employee->name }} responsive and available to the team?
                    </label>
                    <div class="feedback-radio-group">
                        @foreach([1 => 'Always available', 2 => 'Usually available', 3 => 'Sometimes unavailable', 4 => 'Often unavailable'] as $val => $label)
                        <label class="feedback-radio-label">
                            <input type="radio" name="availability_score" value="{{ $val }}" class="feedback-radio"
                                {{ (old('availability_score', $existing?->availability_score ?? 2) == $val) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('availability_score')<span class="form-error">{{ $message }}</span>@enderror
                </div>

                <div class="feedback-question">
                    <label class="feedback-question-label">
                        Did {{ $employee->name }} help teammates when needed?
                    </label>
                    <div class="feedback-radio-group">
                        @foreach([1 => 'Always helped', 2 => 'Usually helped', 3 => 'Sometimes helped', 4 => 'Rarely helped'] as $val => $label)
                        <label class="feedback-radio-label">
                            <input type="radio" name="collaboration_score" value="{{ $val }}" class="feedback-radio"
                                {{ (old('collaboration_score', $existing?->collaboration_score ?? 2) == $val) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('collaboration_score')<span class="form-error">{{ $message }}</span>@enderror
                </div>

            </div>
        </div>

        {{-- SECTION B: CONTEXT --}}
        <div class="feedback-section">
            <h3 class="feedback-section-title">Section B — Context Notes</h3>
            <p class="feedback-section-desc">Text only. These notes help the CEO understand context — they do not directly affect the AI score.</p>

            <div class="feedback-notes-form-grid">
                <div class="form-group">
                    <label class="fv-label">
                        One notable achievement this period
                        <span class="feedback-char-hint">(max 300 chars)</span>
                    </label>
                    <textarea name="notable_achievement" class="fv-input" rows="3" maxlength="300"
                        placeholder="What specific achievement stood out this period?">{{ old('notable_achievement', $existing?->notable_achievement) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="fv-label">
                        One area where more growth is expected
                        <span class="feedback-char-hint">(max 300 chars)</span>
                    </label>
                    <textarea name="area_of_improvement" class="fv-input" rows="3" maxlength="300"
                        placeholder="What one area should they focus on improving?">{{ old('area_of_improvement', $existing?->area_of_improvement) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="fv-label">
                        Special circumstances to consider
                        <span class="feedback-optional">(optional, max 200 chars)</span>
                    </label>
                    <textarea name="special_circumstances" class="fv-input" rows="2" maxlength="200"
                        placeholder="Leave, illness, project challenges, team changes...">{{ old('special_circumstances', $existing?->special_circumstances) }}</textarea>
                </div>
            </div>
        </div>

        {{-- SECTION C: ATTESTATION --}}
        <div class="feedback-section feedback-attestation">
            <label class="feedback-confirm-label">
                <input type="checkbox" name="manager_confirmed" value="1"
                    {{ old('manager_confirmed') ? 'checked' : '' }} class="feedback-confirm-checkbox">
                <span>I confirm this feedback is based on observable work performance only and reflects my honest assessment free from personal bias.</span>
            </label>
            @error('manager_confirmed')<span class="form-error">You must confirm before submitting.</span>@enderror
        </div>

        {{-- ACTIONS --}}
        <div class="feedback-actions">
            <button type="submit" name="action" value="draft" class="btn-secondary">Save as Draft</button>
            <button type="submit" name="action" value="submit" class="btn-primary">Submit Feedback &rarr;</button>
        </div>
    </form>

</div>
@endsection
