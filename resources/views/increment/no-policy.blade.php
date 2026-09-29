<x-app-layout>
@section('title', 'Increment')
<div class="fv-content" style="max-width:500px;margin:80px auto;text-align:center;padding:24px;">
    <div style="font-size:48px;margin-bottom:16px;">&#128202;</div>
    <h2 style="font-size:20px;font-weight:700;color:#18181b;margin:0 0 8px;">No Increment Policy Set</h2>
    <p style="color:#71717a;margin:0 0 24px;line-height:1.6;">Your organization has not configured an increment policy yet. Contact your administrator to set up the increment calculation system.</p>
    <a href="{{ route('dashboard') }}" style="padding:10px 24px;background:#18181b;color:#fff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;">
        Back to Dashboard
    </a>
</div>
</x-app-layout>
