<x-mail::message>
# You've Been Invited! 🎉

Hi there!

**{{ $inviterName }}** has invited you to join **{{ $orgName }}** on OutraqHQ as **{{ $role }}**.

OutraqHQ is a work intelligence platform that tracks team output fairly and transparently — protecting every team member from unfair performance reviews.

<x-mail::panel>
Your role will be: **{{ $role }}**
This invite expires: **{{ $expiresAt }}**
</x-mail::panel>

<x-mail::button :url="$inviteUrl" color="dark">
Accept Invitation
</x-mail::button>

If you did not expect this invitation, you can safely ignore this email.

Thanks,
**The OutraqHQ Team**
</x-mail::message>
