@extends('layouts.app')
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div style="display:flex;flex-direction:column;gap:20px;">

            @if(session('success'))
            <div style="background:#f0fdf4;color:#16a34a;border:1px solid rgba(22,163,74,0.2);padding:12px 16px;border-radius:8px;font-size:0.875rem;font-weight:500;">
                ✓ {{ session('success') }}
            </div>
            @endif

            {{-- GitHub Username --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">GitHub Account</h2>
                </div>
                <form method="POST" action="{{ route('settings.github.update') }}">
                    @csrf
                    <div class="p-lg">
                        <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">GitHub Username</label>
                        <div style="display:flex;align-items:center;border:1px solid {{ $errors->has('github_username') ? '#dc2626' : '#e4e4e7' }};border-radius:6px;overflow:hidden;background:white;">
                            <span style="padding:10px 12px;background:#f4f4f5;border-right:1px solid #e4e4e7;font-size:0.875rem;color:#71717a;white-space:nowrap;">
                                github.com/
                            </span>
                            <input type="text" name="github_username"
                                value="{{ old('github_username', $user->github_username) }}"
                                placeholder="your-username"
                                style="flex:1;border:none;outline:none;padding:10px 12px;font-size:0.875rem;color:#09090b;">
                        </div>
                        @error('github_username')
                        <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                        @enderror
                    </div>
                    <div style="padding:14px 24px;background:#fafafa;border-top:1px solid #e4e4e7;display:flex;justify-content:flex-end;">
                        <button type="submit" style="background:#18181b;color:white;padding:9px 20px;border-radius:6px;font-weight:600;font-size:0.875rem;border:none;cursor:pointer;">
                            Save Username
                        </button>
                    </div>
                </form>
            </div>

            {{-- Connected Projects --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Connected Repositories</h2>
                </div>
                <div style="padding:16px 24px;">
                    @forelse(auth()->user()->organization?->projects ?? [] as $project)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #f4f4f5;">
                        <div>
                            <div style="font-size:0.875rem;font-weight:600;color:#09090b;">{{ $project->name }}</div>
                            <div style="font-size:0.78rem;color:#71717a;">{{ $project->github_owner }}/{{ $project->github_repo }}</div>
                        </div>
                        <a href="{{ route('projects.show', $project->id) }}" style="font-size:0.8rem;color:#18181b;font-weight:500;text-decoration:none;">
                            View →
                        </a>
                    </div>
                    @empty
                    <p style="font-size:0.875rem;color:#71717a;padding:8px 0;">
                        No repositories connected yet.
                        <a href="{{ route('projects.create') }}" style="color:#18181b;font-weight:600;">Add a project →</a>
                    </p>
                    @endforelse
                </div>
            </div>

    </div>

</div>
@endsection
