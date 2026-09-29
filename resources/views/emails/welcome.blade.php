<x-mail::message>
# Welcome to OutraqHQ, {{ $userName }}! 👋

Your account has been created successfully. Here is how to get started:

<x-mail::panel>
**Organization:** {{ $orgName }}
**Your Role:** {{ $userRole }}
**Email:** {{ $userEmail }}
</x-mail::panel>

## Your First Steps:

**1. Set your GitHub username**
Connect your GitHub account so your contributions are tracked automatically.

**2. Log your first work entry**
Take 2 minutes to log what you worked on today. This protects you from unfair performance reviews.

**3. Check your tasks**
See what tasks are assigned to you and get started.

<x-mail::button :url="url('/dashboard')" color="dark">
Go to Dashboard
</x-mail::button>

Need help? Just ask the AI agent on any page — it knows everything!

Thanks,
**The OutraqHQ Team**
</x-mail::message>
