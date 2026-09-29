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

            {{-- Org Info --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Organization Information</h2>
                </div>
                <form method="POST" action="{{ route('settings.organization.update') }}">
                    @csrf
                    <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Organization Name <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $org->name) }}" required class="fv-input" class="w-full">
                            @error('name')
                            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:6px;">Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $org->slug) }}" class="fv-input" class="w-full">
                            @error('slug')
                            <p style="color:#dc2626;font-size:0.78rem;margin-top:4px;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div style="padding:14px 24px;background:#fafafa;border-top:1px solid #e4e4e7;display:flex;justify-content:flex-end;">
                        <button type="submit" style="background:#18181b;color:white;padding:9px 20px;border-radius:6px;font-weight:600;font-size:0.875rem;border:none;cursor:pointer;">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            {{-- Danger Zone --}}
            <div style="background:white;border:1px solid #fca5a5;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #fca5a5;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#dc2626;">Danger Zone</h2>
                    <p style="font-size:0.8rem;color:#71717a;margin-top:4px;">These actions are irreversible. Be careful.</p>
                </div>
                <div class="p-lg">
                    <div style="font-size:0.875rem;font-weight:600;color:#09090b;margin-bottom:4px;">Delete Organization</div>
                    <div style="font-size:0.78rem;color:#71717a;margin-bottom:16px;">Permanently delete this organization and all its data. This cannot be undone.</div>
                    <form method="POST" action="{{ route('settings.organization.delete') }}" onsubmit="return confirm('Are you sure? This cannot be undone.')">
                        @csrf
                        <div style="display:flex;gap:10px;align-items:flex-start;flex-direction:column;">
                            <div style="display:flex;gap:10px;align-items:center;">
                                <input type="text" name="confirmation" placeholder="Type DELETE to confirm" class="fv-input" style="max-width:240px;">
                                <button type="submit" style="background:#dc2626;color:white;padding:9px 20px;border-radius:6px;font-weight:600;font-size:0.875rem;border:none;cursor:pointer;">
                                    Delete Organization
                                </button>
                            </div>
                            @error('confirmation')
                            <p style="color:#dc2626;font-size:0.78rem;margin-top:2px;">{{ $message }}</p>
                            @enderror
                        </div>
                    </form>
                </div>
            </div>

    </div>

</div>
@endsection
