@extends('layouts.app')
@section('title', 'AI Intelligence')

@section('content')
<div class="page-wrapper">

<div class="page-header">
    <div class="page-header-left">
        <h1 class="page-title">AI Intelligence</h1>
        <p class="page-subtitle">Executive insights powered by OutraqHQ AI</p>
    </div>
    <div class="page-header-right">
        <div class="text-right">
            <span class="fv-badge fv-badge-gray" style="font-size:0.8rem;padding:5px 12px;">
                {{ $providerBadge['label'] }}
            </span>
            <div style="font-size:0.72rem;color:var(--text-4);margin-top:4px;">{{ $providerBadge['description'] }}</div>
        </div>
        <form method="POST" action="{{ route('ai.refresh') }}">
            @csrf
            <button type="submit" class="fv-btn fv-btn-secondary">
                ↺ Refresh
            </button>
        </form>
    </div>
</div>

    @if(session('success'))
        <div class="fv-alert fv-alert-success" class="mb-lg">{{ session('success') }}</div>
    @endif

    {{-- Executive Summary Card --}}
    <div class="fv-card" style="margin-bottom:28px;border-left:3px solid var(--accent);padding:0;">
        <div style="padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <h2 style="font-size:1rem;font-weight:700;color:var(--text);margin:0;">Executive Summary</h2>
            <span style="font-size:0.75rem;color:var(--text-4);">
                Generated {{ $summaryData['generated_at'] }}
            </span>
        </div>
        <div class="p-lg">
            <p style="font-size:0.95rem;line-height:1.8;color:var(--text-2);margin:0;white-space:pre-line;">{{ $summaryData['summary'] }}</p>
        </div>
        <div style="padding:12px 24px;border-top:1px solid var(--border);background:var(--surface);border-radius:0 0 12px 12px;">
            <span style="font-size:0.75rem;color:var(--text-4);">AI Engine: {{ $providerName }}</span>
        </div>
    </div>

    {{-- Stats Row --}}
    @php $d = $summaryData['data']; @endphp
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;">
        <div class="fv-stat">
            <div class="fv-stat-label">Total Members</div>
            <div class="fv-stat-number">{{ $d['total_members'] }}</div>
            <div style="font-size:0.75rem;color:var(--text-4);margin-top:4px;">
                {{ $d['inactive_count'] > 0 ? $d['inactive_count'].' inactive' : 'All active' }}
            </div>
        </div>
        <div class="fv-stat">
            <div class="fv-stat-label">Commits This Week</div>
            <div class="fv-stat-number">{{ $d['commits_this_week'] }}</div>
            <div style="font-size:0.75rem;color:var(--text-4);margin-top:4px;">{{ $d['prs_this_week'] }} PRs</div>
        </div>
        <div class="fv-stat">
            <div class="fv-stat-label">Open Blockers</div>
            <div class="fv-stat-number" style="{{ $d['open_blockers'] > 0 ? 'color:var(--danger);' : '' }}">{{ $d['open_blockers'] }}</div>
            <div style="font-size:0.75rem;color:var(--text-4);margin-top:4px;">
                {{ $d['open_blockers'] > 0 ? 'Need attention' : 'Team unblocked' }}
            </div>
        </div>
        <div class="fv-stat">
            <div class="fv-stat-label">Pending Flags</div>
            <div class="fv-stat-number" style="{{ $d['pending_flags'] > 0 ? 'color:var(--warning);' : '' }}">{{ $d['pending_flags'] }}</div>
            <div style="font-size:0.75rem;color:var(--text-4);margin-top:4px;">
                {{ $d['pending_flags'] > 0 ? 'Review needed' : 'No issues' }}
            </div>
        </div>
    </div>

    {{-- Team Performance Table --}}
    <div class="fv-card" style="margin-bottom:28px;">
        <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
            <h2 style="font-size:1rem;font-weight:700;color:var(--text);margin:0;">Team Performance <span style="font-weight:400;color:var(--text-4);">(Last 30 Days)</span></h2>
        </div>
        @if(empty($memberStats))
            <div style="padding:48px 24px;text-align:center;color:var(--text-4);">
                <div style="font-size:0.875rem;">No member activity data yet. Sync GitHub to populate.</div>
            </div>
        @else
        <div class="fv-table-wrap">
            <table class="fv-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Commits</th>
                        <th>PRs</th>
                        <th>Avg Score</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    @php $maxCommits = collect($memberStats)->max('commits') ?: 1; @endphp
                    @foreach($memberStats as $member)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--surface);border:1px solid var(--border);
                                            display:flex;align-items:center;justify-content:center;
                                            font-size:0.72rem;font-weight:800;color:var(--text-2);flex-shrink:0;">
                                    {{ strtoupper(substr($member['name'], 0, 2)) }}
                                </div>
                                <span style="font-weight:600;color:var(--text);">{{ $member['name'] }}</span>
                            </div>
                        </td>
                        <td style="font-weight:700;color:var(--text);">{{ $member['commits'] }}</td>
                        <td style="color:var(--text-2);">{{ $member['prs'] }}</td>
                        <td>
                            <span style="font-size:0.875rem;font-weight:600;color:{{ $member['avg_score'] >= 7 ? '#059669' : ($member['avg_score'] >= 4 ? '#d97706' : 'var(--text-3)') }};">
                                {{ number_format($member['avg_score'], 1) }}
                            </span>
                        </td>
                        <td style="min-width:120px;">
                            @php $pct = $maxCommits > 0 ? round(($member['commits'] / $maxCommits) * 100) : 0; @endphp
                            <div class="flex-center-gap-sm">
                                <div style="flex:1;height:6px;background:var(--border);border-radius:99px;overflow:hidden;">
                                    <div style="height:100%;width:{{ $pct }}%;background:var(--accent);border-radius:99px;transition:width 0.4s ease;"></div>
                                </div>
                                <span style="font-size:0.72rem;color:var(--text-4);min-width:28px;">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Ask AI Section --}}
    <div class="fv-card" style="margin-bottom:28px;" x-data="{
        question: '',
        loading: false,
        answer: null,
        answerQuestion: null,
        answerProvider: null,
        chips: [
            'Who contributed most this month?',
            'Are there inactive team members?',
            'What is blocking the team?',
            'How is the team performing this week?',
            'Are there any fairness concerns?'
        ],
        async ask() {
            if (!this.question.trim() || this.loading) return;
            this.loading = true;
            this.answer = null;
            try {
                const res = await fetch('{{ route('ai.query') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ question: this.question }),
                });
                const data = await res.json();
                this.answer = data.answer;
                this.answerQuestion = data.question;
                this.answerProvider = data.provider;
            } catch(e) {
                this.answer = 'Unable to reach AI engine. Please try again.';
            } finally {
                this.loading = false;
            }
        },
        useChip(chip) {
            this.question = chip;
            this.ask();
        }
    }">
        <div style="padding:20px 24px;border-bottom:1px solid var(--border);">
            <h2 style="font-size:1rem;font-weight:700;color:var(--text);margin:0 0 2px;">Ask AI About Your Team</h2>
            <p style="font-size:0.8rem;color:var(--text-4);margin:0;">Natural language queries about your team output</p>
        </div>
        <div class="p-lg">

            {{-- Input row --}}
            <div style="display:flex;gap:10px;margin-bottom:16px;">
                <input
                    type="text"
                    x-model="question"
                    @keydown.enter="ask()"
                    placeholder="Ask anything about your team…"
                    class="fv-input"
                    style="flex:1;"
                >
                <button
                    @click="ask()"
                    :disabled="loading || !question.trim()"
                    class="fv-btn fv-btn-primary"
                    :style="(loading || !question.trim()) ? 'opacity:0.6;cursor:not-allowed;' : ''"
                    style="white-space:nowrap;display:flex;align-items:center;gap:8px;"
                >
                    <span x-show="!loading">Ask</span>
                    <span x-show="loading" style="display:flex;align-items:center;gap:6px;">
                        <svg style="width:14px;height:14px;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" stroke-linecap="round"/>
                        </svg>
                        Thinking…
                    </span>
                </button>
            </div>

            {{-- Suggestion chips --}}
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;">
                <template x-for="chip in chips" :key="chip">
                    <button
                        @click="useChip(chip)"
                        x-text="chip"
                        style="padding:6px 14px;background:var(--surface);border:1px solid var(--border);
                               border-radius:99px;font-size:0.78rem;font-weight:500;color:var(--text-2);
                               cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;
                               transition:all 0.15s ease;"
                        onmouseover="this.style.background='#f0f0f2';this.style.borderColor='#c8c8cf';"
                        onmouseout="this.style.background='var(--surface)';this.style.borderColor='var(--border)';"
                    ></button>
                </template>
            </div>

            {{-- Answer output --}}
            <div x-show="answer !== null"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:20px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span class="fv-badge fv-badge-gray" style="font-size:0.7rem;" x-text="answerProvider ?? 'AI'"></span>
                    <span style="font-size:0.8rem;color:var(--text-3);font-style:italic;" x-text="'\"' + (answerQuestion ?? '') + '\"'"></span>
                </div>
                <p style="font-size:0.9rem;line-height:1.7;color:var(--text);margin:0;" x-text="answer"></p>
            </div>

        </div>
    </div>

    {{-- Upgrade prompt (rule-based only) --}}
    @if($providerName === 'rule_based')
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;
                display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div style="font-weight:700;color:var(--text);margin-bottom:6px;">Unlock AI-Powered Insights</div>
            <p style="color:var(--text-3);font-size:0.875rem;margin:0;max-width:560px;">
                Upgrade to <strong>Pro</strong> for AI-powered summaries using Llama 3 running locally on your server.
                Upgrade to <strong>Enterprise</strong> for GPT-4o powered insights with deeper team analysis.
            </p>
        </div>
        <a href="#" class="fv-btn fv-btn-primary" style="white-space:nowrap;">
            Upgrade Plan
        </a>
    </div>
    @endif

</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/components.css') }}">
@endpush
@endsection
