@extends('layouts.app')

@section('content')
<div style="max-width:900px;margin:0 auto;padding:32px 24px;">

    {{-- Breadcrumb --}}
    <div style="font-size:0.8rem;color:var(--text-4);margin-bottom:20px;">
        <a href="{{ route('dependency.index') }}" style="color:var(--text-3);text-decoration:none;">Blockers & Dependencies</a>
        <span style="margin:0 6px;">›</span>
        <span style="color:var(--text);">{{ Str::limit($blocker->title, 60) }}</span>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="fv-alert fv-alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="fv-alert fv-alert-warning" style="margin-bottom:20px;">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="fv-alert fv-alert-error" style="margin-bottom:20px;">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-lg">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div style="flex:1;min-width:0;">
                <h1 style="font-size:1.4rem;font-weight:800;color:var(--text);margin:0 0 8px;">{{ $blocker->title }}</h1>
                <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                    @php
                        $pc = $blocker->priorityColor();
                        $priorityClass = $pc === 'red' ? 'fv-badge fv-badge-error' : ($pc === 'amber' ? 'fv-badge fv-badge-warning' : 'fv-badge fv-badge-gray');
                    @endphp
                    <span class="{{ $priorityClass }}" style="text-transform:capitalize;">{{ $blocker->priority }}</span>
                    @if($blocker->status === 'open')
                        <span class="fv-badge fv-badge-error">Open</span>
                    @elseif($blocker->status === 'escalated')
                        <span style="background:#fef3c7;color:#92400e;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:99px;">Escalated</span>
                    @elseif($blocker->status === 'resolved')
                        <span class="fv-badge fv-badge-success">Resolved</span>
                    @endif
                    @if($blocker->isExternal())
                        <span style="background:#f0fdf4;color:#16a34a;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:99px;">External</span>
                    @endif
                    @if($blocker->isOwnershipDisputed())
                        <span style="background:#fef2f2;color:#dc2626;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:99px;">⚠️ Disputed</span>
                    @endif
                </div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
                @if($blocker->status !== 'resolved')
                    <div style="font-size:1.5rem;font-weight:800;color:{{ $blocker->needsAttention() ? 'var(--danger)' : 'var(--text)' }};">
                        {{ $blocker->daysOpen() }}
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-4);text-transform:uppercase;letter-spacing:0.05em;">days open</div>
                @else
                    <div style="font-size:1.5rem;font-weight:800;color:#16a34a;">{{ $blocker->days_to_resolve ?? $blocker->daysOpen() }}</div>
                    <div style="font-size:0.72rem;color:var(--text-4);text-transform:uppercase;letter-spacing:0.05em;">days to resolve</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Status Banners --}}
    @if($blocker->isOwnershipDisputed())
    <div style="background:#fef2f2;border:1.5px solid #f87171;border-radius:10px;padding:16px 20px;margin-bottom:20px;">
        <div style="font-weight:800;color:#991b1b;font-size:0.9rem;margin-bottom:6px;">⚠️ OWNERSHIP DISPUTED</div>
        <div style="font-size:0.85rem;color:#7f1d1d;line-height:1.6;">
            <strong>{{ $blocker->blockingUser?->name ?? 'Unknown' }}</strong> claimed this is not their responsibility
            @if($blocker->dispute_raised_at) on <strong>{{ $blocker->dispute_raised_at->format('M j, Y \a\t g:i A') }}</strong>@endif.<br>
            <strong>Dispute reason:</strong> {{ $blocker->dispute_reason }}<br>
            <span style="font-size:0.8rem;color:#991b1b;margin-top:6px;display:block;">This has been escalated to leadership for review. The accountability record has been created.</span>
        </div>
    </div>
    @endif

    @if($blocker->needsAttention() && !$blocker->isOwnershipDisputed())
    <div style="background:#fffbeb;border:1.5px solid #fbbf24;border-radius:10px;padding:14px 20px;margin-bottom:20px;">
        <div style="font-size:0.875rem;color:#78350f;">
            ⏰ <strong>This blocker has been open for {{ $blocker->daysOpen() }} days.</strong> Immediate attention required.
        </div>
    </div>
    @endif

    {{-- Two-column layout --}}
    <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">

        {{-- LEFT: Details + Evidence + Timeline --}}
        <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:16px;">

            {{-- Details card --}}
            <div class="fv-card" style="padding:20px 24px;">
                <h2 style="font-size:0.875rem;font-weight:700;color:var(--text);margin:0 0 16px;">Blocker Details</h2>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <div style="display:flex;gap:8px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-4);width:100px;flex-shrink:0;padding-top:2px;">Reporter</div>
                        <div style="font-size:0.875rem;color:var(--text);font-weight:500;">{{ $blocker->reportedBy?->name ?? '—' }}</div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-4);width:100px;flex-shrink:0;padding-top:2px;">Blocked</div>
                        <div style="font-size:0.875rem;color:var(--text);font-weight:500;">{{ $blocker->blockedUser?->name ?? '—' }}</div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-4);width:100px;flex-shrink:0;padding-top:2px;">Blocking</div>
                        <div style="flex:1;min-width:0;">
                            @if($blocker->blockingUser)
                                <div style="font-size:0.875rem;color:var(--text);font-weight:500;">{{ $blocker->blockingUser->name }}</div>
                            @elseif($blocker->blocker_type === 'internal_other_team' && $blocker->external_person_name)
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;background:#dbeafe;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#1d4ed8;flex-shrink:0;">
                                        {{ strtoupper(substr($blocker->external_person_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-size:0.875rem;font-weight:600;color:#09090b;">{{ $blocker->external_person_name }}</div>
                                        <div style="font-size:0.75rem;color:#71717a;">{{ $blocker->external_person_company }} · Same Company</div>
                                        @if($blocker->external_person_contact)
                                            <div style="font-size:0.72rem;color:#71717a;">{{ $blocker->external_person_contact }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div style="margin-top:8px;background:#eff6ff;border-radius:6px;padding:8px 12px;font-size:0.75rem;color:#1d4ed8;">
                                    ℹ️ This person is from your company but not in your OutraqHQ team. Consider inviting them or escalating to management to resolve cross-team blockers.
                                </div>
                            @elseif($blocker->external_person_name)
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:32px;height:32px;background:#fef3c7;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#d97706;flex-shrink:0;">
                                        {{ strtoupper(substr($blocker->external_person_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div style="font-size:0.875rem;font-weight:600;color:#09090b;">{{ $blocker->external_person_name }}</div>
                                        @if($blocker->external_person_company)
                                            <div style="font-size:0.75rem;color:#71717a;">{{ $blocker->external_person_company }} · External</div>
                                        @endif
                                        @if($blocker->external_person_contact)
                                            <div style="font-size:0.72rem;color:#71717a;">{{ $blocker->external_person_contact }}</div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span style="color:var(--text-4);">—</span>
                            @endif
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-4);width:100px;flex-shrink:0;padding-top:2px;">Project</div>
                        <div style="font-size:0.875rem;color:var(--text);">{{ $blocker->project?->name ?? '—' }}</div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-4);width:100px;flex-shrink:0;padding-top:2px;">Type</div>
                        <div style="font-size:0.875rem;color:var(--text);text-transform:capitalize;">{{ str_replace('_', ' ', $blocker->blocker_type) }}</div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-4);width:100px;flex-shrink:0;padding-top:2px;">Created</div>
                        <div style="font-size:0.875rem;color:var(--text);">{{ $blocker->created_at->format('M j, Y \a\t g:i A') }}</div>
                    </div>
                    @if($blocker->due_date)
                    <div style="display:flex;gap:8px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-4);width:100px;flex-shrink:0;padding-top:2px;">Due</div>
                        <div style="font-size:0.875rem;color:{{ $blocker->due_date->isPast() ? 'var(--danger)' : 'var(--text)' }};font-weight:{{ $blocker->due_date->isPast() ? '700' : '400' }};">
                            {{ $blocker->due_date->format('M j, Y') }}
                            @if($blocker->due_date->isPast()) (overdue)@endif
                        </div>
                    </div>
                    @endif
                </div>
                @if($blocker->description)
                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
                    <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--text-4);margin-bottom:8px;">Description</div>
                    <div style="font-size:0.875rem;color:var(--text-2);line-height:1.6;">{{ $blocker->description }}</div>
                </div>
                @endif
            </div>

            {{-- Evidence card --}}
            @if($blocker->evidence_notes)
            <div class="fv-card" style="padding:20px 24px;">
                <h2 style="font-size:0.875rem;font-weight:700;color:var(--text);margin:0 0 12px;">📋 Evidence & Documentation</h2>
                <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:14px;font-size:0.82rem;color:var(--text-2);line-height:1.7;font-family:monospace;white-space:pre-wrap;word-break:break-word;">{{ $blocker->evidence_notes }}</div>
            </div>
            @endif

            {{-- Timeline --}}
            <div class="fv-card" style="padding:20px 24px;">
                <h2 style="font-size:0.875rem;font-weight:700;color:var(--text);margin:0 0 16px;">📜 Blocker History</h2>
                @if($blocker->responses->isEmpty())
                    <div style="font-size:0.875rem;color:var(--text-4);font-style:italic;">No responses yet. Be the first to respond.</div>
                @else
                <div style="display:flex;flex-direction:column;gap:14px;">
                    @foreach($blocker->responses as $response)
                    @php
                        $badgeStyle = match($response->response_type) {
                            'acknowledged' => 'background:#f0fdf4;color:#16a34a;',
                            'disputed'     => 'background:#fef2f2;color:#dc2626;',
                            'escalated'    => 'background:#fffbeb;color:#92400e;',
                            'resolved'     => 'background:#f0fdf4;color:#16a34a;',
                            default        => 'background:#f4f4f5;color:#71717a;',
                        };
                        $badgeLabel = match($response->response_type) {
                            'acknowledged' => '✓ Acknowledged',
                            'disputed'     => '⚠️ Disputed Ownership',
                            'escalated'    => '🔺 Escalated',
                            'resolved'     => '✅ Resolved',
                            default        => '💬 Comment',
                        };
                    @endphp
                    <div style="display:flex;gap:12px;">
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.72rem;color:var(--text-2);flex-shrink:0;">
                            {{ strtoupper(substr($response->user?->name ?? 'SY', 0, 2)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                                <span style="font-size:0.82rem;font-weight:600;color:var(--text);">{{ $response->user?->name ?? 'System' }}</span>
                                <span style="font-size:0.68rem;font-weight:700;padding:2px 8px;border-radius:99px;{{ $badgeStyle }}">{{ $badgeLabel }}</span>
                                <span style="font-size:0.72rem;color:var(--text-4);">{{ $response->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="font-size:0.82rem;color:var(--text-2);line-height:1.5;background:var(--surface);border-radius:8px;padding:8px 12px;">{{ $response->message }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

        </div>

        {{-- RIGHT: Actions --}}
        <div style="width:260px;flex-shrink:0;display:flex;flex-direction:column;gap:14px;">

            @php
                $currentUserId = auth()->id();
                $isBlockedUser  = $currentUserId === $blocker->blocked_user_id;
                $isBlockingUser = $currentUserId === $blocker->blocking_user_id;
                $isLeader = auth()->user()->hasAnyRole(['owner', 'admin', 'super_admin']);
                $isOpen   = in_array($blocker->status, ['open', 'escalated']);
            @endphp

            {{-- Blocked person actions --}}
            @if($isBlockedUser && $isOpen)
            <div class="fv-card" style="padding:16px 20px;">
                <div style="font-size:0.8rem;font-weight:700;color:var(--text);margin-bottom:12px;">Your Actions</div>

                {{-- Raise dispute --}}
                @if(!$blocker->isOwnershipDisputed() && $blocker->blockingUser)
                <div x-data="{ open: false }" style="margin-bottom:10px;">
                    <button @click="open = !open"
                        style="width:100%;background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;border-radius:8px;padding:9px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;text-align:left;">
                        ⚠️ Raise Ownership Dispute
                    </button>
                    <div x-show="open" x-transition class="mt-sm">
                        <form method="POST" action="{{ route('dependency.dispute', $blocker->id) }}">
                            @csrf
                            <textarea name="dispute_reason" rows="3" class="fv-input" required
                                placeholder="Explain why you believe {{ $blocker->blockingUser?->name }} owns this…"
                                style="margin-bottom:8px;resize:vertical;font-size:0.8rem;"></textarea>
                            <button type="submit" style="width:100%;background:#dc2626;color:white;border:none;border-radius:8px;padding:8px;font-size:0.8rem;font-weight:700;cursor:pointer;"
                                onclick="return confirm('This will create a permanent accountability record. Continue?')">
                                Record Dispute
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Mark resolved --}}
                @can('resolve_blockers')
                <div x-data="{ open: false }" style="margin-bottom:10px;">
                    <button @click="open = !open"
                        style="width:100%;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:8px;padding:9px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;text-align:left;">
                        ✓ Mark as Resolved
                    </button>
                    <div x-show="open" x-transition class="mt-sm">
                        <form method="POST" action="{{ route('dependency.resolve', $blocker->id) }}">
                            @csrf
                            <input type="text" name="resolution_proof" class="fv-input"
                                placeholder="Link or reference as proof (optional)"
                                style="margin-bottom:8px;font-size:0.8rem;">
                            <textarea name="resolution_notes" rows="2" class="fv-input"
                                placeholder="How was it resolved?"
                                style="margin-bottom:8px;resize:vertical;font-size:0.8rem;"></textarea>
                            <button type="submit" style="width:100%;background:#16a34a;color:white;border:none;border-radius:8px;padding:8px;font-size:0.8rem;font-weight:700;cursor:pointer;">
                                Confirm Resolution
                            </button>
                        </form>
                    </div>
                </div>
                @endcan

            </div>
            @endif

            {{-- Blocking person actions --}}
            @if($isBlockingUser && $isOpen)
            <div class="fv-card" style="padding:16px 20px;">
                <div style="font-size:0.8rem;font-weight:700;color:var(--text);margin-bottom:12px;">You Are the Blocking Person</div>

                <form method="POST" action="{{ route('dependency.acknowledge', $blocker->id) }}" style="margin-bottom:10px;">
                    @csrf
                    <input type="hidden" name="message" value="I acknowledge responsibility for this blocker and will resolve it.">
                    <button type="submit"
                        style="width:100%;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:8px;padding:9px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;">
                        ✓ Acknowledge Responsibility
                    </button>
                </form>

                @if(!$blocker->isOwnershipDisputed())
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                        style="width:100%;background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;border-radius:8px;padding:9px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;text-align:left;">
                        ⚠️ Dispute Ownership
                    </button>
                    <div x-show="open" x-transition class="mt-sm">
                        <form method="POST" action="{{ route('dependency.dispute', $blocker->id) }}">
                            @csrf
                            <textarea name="dispute_reason" rows="3" class="fv-input" required
                                placeholder="Explain why this is not your responsibility…"
                                style="margin-bottom:8px;resize:vertical;font-size:0.8rem;"></textarea>
                            <button type="submit" style="width:100%;background:#dc2626;color:white;border:none;border-radius:8px;padding:8px;font-size:0.8rem;font-weight:700;cursor:pointer;"
                                onclick="return confirm('This will be recorded permanently. Continue?')">
                                Record Dispute
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
            @endif

            {{-- Leader actions --}}
            @if($isLeader && $isOpen)
            <div class="fv-card" style="padding:16px 20px;">
                <div style="font-size:0.8rem;font-weight:700;color:var(--text);margin-bottom:12px;">Leadership Actions</div>

                @can('escalate_blockers')
                <form method="POST" action="{{ route('dependency.escalate', $blocker->id) }}" class="mb-sm">
                    @csrf
                    <button type="submit" style="width:100%;background:#fffbeb;color:#92400e;border:1px solid #fbbf24;border-radius:8px;padding:9px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;">
                        🔺 Escalate
                    </button>
                </form>
                @endcan

                @can('resolve_blockers')
                <div x-data="{ open: false }">
                    <button @click="open = !open"
                        style="width:100%;background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:8px;padding:9px 14px;font-size:0.8rem;font-weight:700;cursor:pointer;text-align:left;">
                        ✓ Force Resolve
                    </button>
                    <div x-show="open" x-transition class="mt-sm">
                        <form method="POST" action="{{ route('dependency.resolve', $blocker->id) }}">
                            @csrf
                            <input type="text" name="resolution_proof" class="fv-input"
                                placeholder="Resolution reference" style="margin-bottom:8px;font-size:0.8rem;">
                            <textarea name="resolution_notes" rows="2" class="fv-input"
                                placeholder="Leadership decision…" style="margin-bottom:8px;resize:vertical;font-size:0.8rem;"></textarea>
                            <button type="submit" style="width:100%;background:#16a34a;color:white;border:none;border-radius:8px;padding:8px;font-size:0.8rem;font-weight:700;cursor:pointer;">
                                Force Resolve
                            </button>
                        </form>
                    </div>
                </div>
                @endcan
            </div>
            @endif

            {{-- Add comment (all parties) --}}
            @if($isOpen)
            <div class="fv-card" style="padding:16px 20px;" x-data="{ open: false }">
                <button @click="open = !open"
                    style="background:none;border:none;font-size:0.8rem;font-weight:700;color:var(--text);cursor:pointer;padding:0;text-align:left;width:100%;">
                    💬 Add Comment <span x-text="open ? '▲' : '▼'" style="color:var(--text-4);"></span>
                </button>
                <div x-show="open" x-transition style="margin-top:10px;">
                    <form method="POST" action="{{ route('dependency.comment', $blocker->id) }}">
                        @csrf
                        <textarea name="message" rows="3" class="fv-input" required
                            placeholder="Add context, updates, or notes…"
                            style="margin-bottom:8px;resize:vertical;font-size:0.8rem;"></textarea>
                        <button type="submit" class="fv-btn fv-btn-primary" style="font-size:0.78rem;padding:7px 14px;width:100%;">Post Comment</button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Back link --}}
            <a href="{{ route('dependency.index') }}"
                style="display:block;text-align:center;font-size:0.8rem;color:var(--text-3);text-decoration:none;padding:8px;">
                ← Back to all blockers
            </a>
        </div>

    </div>

    {{-- Resolution card --}}
    @if($blocker->status === 'resolved')
    <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:20px 24px;margin-top:20px;">
        <div style="font-weight:800;color:#14532d;font-size:0.9rem;margin-bottom:10px;">✅ Blocker Resolved</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:0.875rem;">
            <div>
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#16a34a;margin-bottom:3px;">Resolved By</div>
                <div style="color:#14532d;font-weight:600;">{{ $blocker->resolvedBy?->name ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#16a34a;margin-bottom:3px;">Resolved At</div>
                <div style="color:#14532d;">{{ $blocker->resolved_at?->format('M j, Y \a\t g:i A') ?? '—' }}</div>
            </div>
            @if($blocker->days_to_resolve !== null)
            <div>
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#16a34a;margin-bottom:3px;">Time to Resolve</div>
                <div style="color:#14532d;font-weight:600;">{{ $blocker->days_to_resolve }} day(s)</div>
            </div>
            @endif
            @if($blocker->resolution_proof)
            <div style="grid-column:span 2;">
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#16a34a;margin-bottom:3px;">Resolution Proof</div>
                <div style="color:#14532d;font-family:monospace;font-size:0.82rem;">{{ $blocker->resolution_proof }}</div>
            </div>
            @endif
            @if($blocker->resolution_notes)
            <div style="grid-column:span 2;">
                <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#16a34a;margin-bottom:3px;">Notes</div>
                <div style="color:#14532d;line-height:1.5;">{{ $blocker->resolution_notes }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
