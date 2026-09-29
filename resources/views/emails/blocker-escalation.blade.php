<x-mail::message>
# Blocker Needs Immediate Attention 🚨

Hi {{ $recipientName }},

A blocker has been open for **{{ $daysOpen }} days** and requires your immediate attention.

<x-mail::panel>
**Blocker:** {{ $blockerTitle }}

**Reported by:** {{ $reportedBy }}

**Blocking:** {{ $blockingPerson }}

**Priority:** {{ $priority }}

**Days Open:** {{ $daysOpen }} days

@if($ownershipDisputed)
⚠️ **OWNERSHIP DISPUTED** — This blocker has an unresolved ownership dispute.
@endif
</x-mail::panel>

<x-mail::button :url="$blockerUrl" color="dark">
View and Resolve Blocker
</x-mail::button>

Thanks,
**OutraqHQ AI Agent**
</x-mail::message>
