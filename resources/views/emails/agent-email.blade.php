<x-mail::message>
# {{ $subject }}

{!! nl2br(e($body)) !!}

@if($actionUrl)
<x-mail::button :url="$actionUrl" color="dark">
{{ $actionLabel ?: 'View Details' }}
</x-mail::button>
@endif

---
*This email was sent by {{ $senderContext }} on behalf of your organization.*

Thanks,
**OutraqHQ AI Agent**
</x-mail::message>
