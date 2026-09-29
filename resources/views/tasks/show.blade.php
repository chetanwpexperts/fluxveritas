@extends('layouts.app')
@section('title', ($task->ticket_number ?? 'Task') . ' — ' . $task->title)
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px;">

    {{-- Breadcrumb --}}
    <div style="display:flex;align-items:center;gap:6px;font-size:0.75rem;color:#a1a1aa;margin-bottom:16px;">
        <a href="{{ route('tasks.index') }}" style="color:#71717a;text-decoration:none;font-weight:600;">Tasks</a>
        <span>›</span>
        @if($task->project)
        <a href="{{ route('projects.show',$task->project->id) }}" style="color:#71717a;text-decoration:none;">{{ $task->project->name }}</a>
        <span>›</span>
        @endif
        <span style="font-family:monospace;color:#a1a1aa;">{{ $task->ticket_number ?? 'Task' }}</span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

        {{-- LEFT COLUMN --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Header --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:20px 22px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span style="font-size:1.1rem;">{{ $task->getTypeIcon() }}</span>
                    <span style="font-size:0.78rem;font-family:monospace;color:#a1a1aa;font-weight:600;background:#f4f4f5;padding:2px 8px;border-radius:5px;">{{ $task->ticket_number ?? '—' }}</span>
                    <span style="padding:3px 8px;border-radius:99px;font-size:0.7rem;font-weight:700;color:white;background:{{ $task->getStatusColor() }};">{{ $task->getStatusLabel() }}</span>
                    <span style="font-size:0.85rem;">{{ $task->getPriorityIcon() }}</span>
                </div>
                <h1 style="font-size:1.25rem;font-weight:800;color:#09090b;margin:0 0 12px;line-height:1.3;">{{ $task->title }}</h1>
                @if($task->description)
                <div style="font-size:0.85rem;color:#3f3f46;line-height:1.7;white-space:pre-line;">{{ $task->description }}</div>
                @else
                <div style="font-size:0.82rem;color:#a1a1aa;font-style:italic;">No description provided.</div>
                @endif
            </div>

            {{-- Sub-tasks --}}
            @if($task->subtasks->count() > 0 || !$task->parent_task_id)
            <div style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:18px 22px;">
                <div style="font-size:0.85rem;font-weight:800;color:#09090b;margin-bottom:12px;">Sub-tasks ({{ $task->subtasks->count() }})</div>
                @foreach($task->subtasks as $sub)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f4f4f5;">
                    <span style="font-size:0.8rem;">{{ $sub->getTypeIcon() }}</span>
                    <div style="flex:1;">
                        <a href="{{ route('tasks.show',$sub->id) }}" style="font-size:0.8rem;font-weight:600;color:#09090b;text-decoration:none;">{{ $sub->title }}</a>
                    </div>
                    @if($sub->assignee)
                    <span style="font-size:0.72rem;color:#71717a;">{{ explode(' ',$sub->assignee->name)[0] }}</span>
                    @endif
                    <span style="padding:2px 7px;border-radius:99px;font-size:0.65rem;font-weight:700;color:white;background:{{ $sub->getStatusColor() }};">{{ $sub->getStatusLabel() }}</span>
                </div>
                @endforeach
                <a href="{{ route('tasks.create',['parent_id'=>$task->id,'project_id'=>$task->project_id]) }}" style="display:inline-block;margin-top:10px;font-size:0.75rem;color:#71717a;text-decoration:none;font-weight:600;">+ Add sub-task</a>
            </div>
            @endif

            {{-- Timeline: Comments + History --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:18px 22px;">
                <div style="font-size:0.85rem;font-weight:800;color:#09090b;margin-bottom:16px;">Activity</div>

                <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
                    @forelse($timeline as $entry)
                    @if($entry['type'] === 'comment')
                    @php $c = $entry['item']; @endphp
                    <div style="display:flex;gap:10px;">
                        <div style="width:28px;height:28px;border-radius:50%;background:#f4f4f5;border:1.5px solid #e4e4e7;display:flex;align-items:center;justify-content:center;font-size:0.62rem;font-weight:800;color:#3f3f46;flex-shrink:0;">
                            {{ strtoupper(substr($c->user->name,0,2)) }}
                        </div>
                        <div style="flex:1;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                                <span style="font-size:0.78rem;font-weight:700;color:#09090b;">{{ $c->user->name }}</span>
                                <span style="font-size:0.68rem;color:#a1a1aa;">{{ $c->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="background:#f9f9fb;border:1px solid #f4f4f5;border-radius:8px;padding:10px 12px;font-size:0.8rem;color:#3f3f46;line-height:1.5;">{{ $c->comment }}</div>
                        </div>
                    </div>
                    @else
                    @php $h = $entry['item']; @endphp
                    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                        <div style="width:6px;height:6px;border-radius:50%;background:#e4e4e7;flex-shrink:0;margin-left:11px;"></div>
                        <span style="font-size:0.75rem;color:#a1a1aa;">
                            <strong style="color:#71717a;">{{ $h->user->name }}</strong>
                            {{ str_replace('_', ' ', $h->action) }}
                            @if($h->old_value && $h->new_value)
                            from <strong style="color:#71717a;">{{ $h->old_value }}</strong> to <strong style="color:#71717a;">{{ $h->new_value }}</strong>
                            @elseif($h->new_value)
                            · {{ $h->new_value }}
                            @endif
                            · {{ $h->created_at->diffForHumans() }}
                        </span>
                    </div>
                    @endif
                    @empty
                    <div style="font-size:0.82rem;color:#a1a1aa;">No activity yet.</div>
                    @endforelse
                </div>

                {{-- Comment input --}}
                <form method="POST" action="{{ route('tasks.comment', $task->id) }}" style="display:flex;flex-direction:column;gap:8px;">
                    @csrf
                    <textarea name="comment" placeholder="Add a comment..." rows="3"
                        style="width:100%;border:1px solid #e4e4e7;border-radius:8px;padding:10px 12px;font-size:0.82rem;color:#09090b;font-family:'Plus Jakarta Sans',sans-serif;resize:vertical;outline:none;box-sizing:border-box;"></textarea>
                    <div class="text-right">
                        <button type="submit" style="padding:7px 16px;background:#09090b;color:white;border:none;border-radius:7px;font-size:0.78rem;font-weight:700;cursor:pointer;">Add Comment</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- RIGHT COLUMN --}}
        <div style="display:flex;flex-direction:column;gap:12px;">

            {{-- Quick update form --}}
            <form method="POST" action="{{ route('tasks.update', $task->id) }}" style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:16px 18px;">
                @csrf @method('PATCH')
                <div style="font-size:0.82rem;font-weight:800;color:#09090b;margin-bottom:12px;">Details</div>

                @foreach([
                    ['Status','status',['backlog'=>'Backlog','todo'=>'To Do','in_progress'=>'In Progress','in_review'=>'In Review','blocked'=>'Blocked','done'=>'Done','cancelled'=>'Cancelled'],$task->status],
                    ['Priority','priority',['critical'=>'🔴 Critical','high'=>'🟠 High','medium'=>'🟡 Medium','low'=>'🟢 Low'],$task->priority],
                    ['Type','type',['task'=>'📋 Task','bug'=>'🐛 Bug','feature'=>'✨ Feature','improvement'=>'📈 Improvement','story'=>'📖 Story','epic'=>'⚡ Epic','question'=>'❓ Question','incident'=>'🚨 Incident'],$task->type],
                ] as [$lbl,$name,$opts,$val])
                <div style="margin-bottom:10px;">
                    <div style="font-size:0.68rem;font-weight:700;color:#a1a1aa;text-transform:uppercase;margin-bottom:4px;">{{ $lbl }}</div>
                    <select name="{{ $name }}" style="width:100%;padding:6px 8px;border:1px solid #e4e4e7;border-radius:7px;font-size:0.78rem;color:#3f3f46;background:white;cursor:pointer;">
                        @foreach($opts as $v=>$l)
                        <option value="{{ $v }}" {{ $val===$v?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach

                <div style="margin-bottom:10px;">
                    <div style="font-size:0.68rem;font-weight:700;color:#a1a1aa;text-transform:uppercase;margin-bottom:4px;">Assignee</div>
                    <select name="assigned_to" style="width:100%;padding:6px 8px;border:1px solid #e4e4e7;border-radius:7px;font-size:0.78rem;color:#3f3f46;background:white;cursor:pointer;">
                        <option value="">Unassigned</option>
                        @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ $task->assigned_to==$m->id?'selected':'' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:10px;">
                    <div style="font-size:0.68rem;font-weight:700;color:#a1a1aa;text-transform:uppercase;margin-bottom:4px;">Sprint</div>
                    <select name="sprint_id" style="width:100%;padding:6px 8px;border:1px solid #e4e4e7;border-radius:7px;font-size:0.78rem;color:#3f3f46;background:white;cursor:pointer;">
                        <option value="">No Sprint</option>
                        @foreach($sprints as $s)
                        <option value="{{ $s->id }}" {{ $task->sprint_id==$s->id?'selected':'' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:12px;">
                    <div style="font-size:0.68rem;font-weight:700;color:#a1a1aa;text-transform:uppercase;margin-bottom:4px;">Reporter</div>
                    <div style="font-size:0.78rem;color:#3f3f46;">{{ $task->reporter?->name ?? '—' }}</div>
                </div>

                <button type="submit" style="width:100%;padding:8px;background:#09090b;color:white;border:none;border-radius:7px;font-size:0.78rem;font-weight:700;cursor:pointer;">Save Changes</button>
            </form>

            {{-- Dates --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:16px 18px;">
                <div style="font-size:0.82rem;font-weight:800;color:#09090b;margin-bottom:12px;">Dates</div>
                @foreach([
                    ['Created', $task->created_at?->format('M j, Y')],
                    ['Due Date', $task->due_date?->format('M j, Y')],
                    ['Started', $task->started_at?->format('M j, Y H:i')],
                    ['Completed', $task->completed_at?->format('M j, Y H:i')],
                ] as [$lbl,$val])
                <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #fafafa;">
                    <span style="font-size:0.72rem;color:#a1a1aa;font-weight:600;">{{ $lbl }}</span>
                    <span style="font-size:0.75rem;color:#3f3f46;font-weight:600;">{{ $val ?? '—' }}</span>
                </div>
                @endforeach
                @if($task->estimated_hours || $task->actual_hours)
                <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #fafafa;">
                    <span style="font-size:0.72rem;color:#a1a1aa;font-weight:600;">Estimated</span>
                    <span style="font-size:0.75rem;color:#3f3f46;font-weight:600;">{{ $task->estimated_hours ? $task->estimated_hours . 'h' : '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:5px 0;">
                    <span style="font-size:0.72rem;color:#a1a1aa;font-weight:600;">Actual</span>
                    <span style="font-size:0.75rem;color:#3f3f46;font-weight:600;">{{ $task->actual_hours ? $task->actual_hours . 'h' : '—' }}</span>
                </div>
                @endif
            </div>

            {{-- Project info --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:16px 18px;">
                <div style="font-size:0.82rem;font-weight:800;color:#09090b;margin-bottom:10px;">Project</div>
                @if($task->project)
                <a href="{{ route('projects.show',$task->project->id) }}" style="font-size:0.82rem;color:#09090b;font-weight:700;text-decoration:none;">{{ $task->project->name }}</a>
                @endif
                @if($task->sprint)
                <div style="margin-top:6px;font-size:0.75rem;color:#71717a;">Sprint: <a href="{{ route('sprints.show', $task->sprint->id) }}" style="color:#3b82f6;text-decoration:none;font-weight:600;">{{ $task->sprint->name }}</a></div>
                @endif
                @if($task->parent)
                <div style="margin-top:6px;font-size:0.75rem;color:#71717a;">Parent: <a href="{{ route('tasks.show',$task->parent->id) }}" style="color:#3b82f6;text-decoration:none;font-weight:600;">{{ $task->parent->ticket_number }}</a></div>
                @endif
            </div>

            {{-- Actions --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:12px;padding:16px 18px;">
                <form method="POST" action="{{ route('tasks.destroy',$task->id) }}" onsubmit="return confirm('Archive this task?');">
                    @csrf @method('DELETE')
                    <button type="submit" style="width:100%;padding:7px;background:transparent;border:1px solid #fecaca;color:#dc2626;border-radius:7px;font-size:0.75rem;font-weight:600;cursor:pointer;">Archive Task</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
