<x-mail::message>
# Weekly Team Report 📊

Hi {{ $recipientName }},

Here is your team summary for the week of **{{ $weekStart }} — {{ $weekEnd }}**

<x-mail::panel>
👥 **Team Members:** {{ $stats['total_members'] }}
📝 **Work Logs:** {{ $stats['total_logs'] }} entries
⏱️ **Hours Tracked:** {{ $stats['total_hours'] }}h
✅ **Tasks Completed:** {{ $stats['tasks_done'] ?? 0 }}
🚧 **Open Blockers:** {{ $stats['open_blockers'] }}
⚖️ **Pending Flags:** {{ $stats['pending_flags'] }}
</x-mail::panel>

@if(($stats['open_blockers'] ?? 0) > 0)
## ⚠️ Action Required

{{ $stats['open_blockers'] }} blocker(s) need your attention.
@endif

<x-mail::button :url="url('/ceo')" color="dark">
View Full Report
</x-mail::button>

Thanks,
**OutraqHQ AI Agent**
</x-mail::message>
