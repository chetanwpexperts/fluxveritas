<x-mail::message>
# Log Your Work Today 📝

Hi {{ $userName }},

You haven't logged any work activities for today yet.

**Why this matters:**
Your work log is your protection. It proves what you worked on and prevents unfair performance reviews.

It only takes **2 minutes** to log your day.

<x-mail::panel>
**Quick categories to log:**
- Meetings attended
- Development work done
- Code reviews completed
- Calls or client visits
- Research or planning
</x-mail::panel>

<x-mail::button :url="url('/work-log/today')" color="dark">
Log Today's Work Now
</x-mail::button>

Your data. Your protection.

Thanks,
**OutraqHQ AI Agent**
</x-mail::message>
