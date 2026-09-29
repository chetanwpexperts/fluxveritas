<x-mail::message>
# Hi {{ $userName }},

**{{ $orgName }}** has added you to OutraqHQ, where your team handles leave, documents, work logs and reviews.

Set a password to sign in:

<x-mail::button :url="$url" color="dark">
Set my password
</x-mail::button>

This link works once and expires in {{ $days }} days. If it has expired, use "Forgot password" on the sign-in page with this email address.

Thanks,
**The OutraqHQ Team**
</x-mail::message>
