@extends('layouts.public')
@section('title', 'Contact')
@section('content')

<div style="max-width:560px;margin:0 auto;padding:3rem 1rem">

  <h1 style="font-size:28px;font-weight:500;color:#18181b;margin-bottom:6px;text-align:center">
    Get in touch
  </h1>
  <p style="font-size:14px;color:#6b7280;margin-bottom:2rem;text-align:center">
    Tell us about your team and we'll be in touch shortly.
  </p>

  @if(session('success'))
  <div style="background:#eaf3de;border:0.5px solid #c3e6a0;color:#3b6d11;
              padding:12px 16px;border-radius:8px;margin-bottom:1.5rem;font-size:13px">
    {{ session('success') }}
  </div>
  @endif

  <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:16px;padding:2rem">
    <form method="POST" action="{{ route('contact.submit') }}">
      @csrf
      @if($planInterest)
        <input type="hidden" name="plan_interest" value="{{ $planInterest }}">
        <div style="background:#f3f4f6;border-radius:8px;padding:8px 12px;margin-bottom:1.25rem;
                    font-size:12px;color:#6b7280">
          Inquiry about: <strong style="color:#18181b;text-transform:capitalize">{{ $planInterest }}</strong> plan
        </div>
      @endif

      <label style="display:block;font-size:13px;color:#18181b;margin-bottom:6px">Name</label>
      <input type="text" name="name" value="{{ old('name') }}" required
        style="width:100%;padding:10px 12px;border:0.5px solid #e5e7eb;border-radius:8px;
               font-size:14px;margin-bottom:1rem;box-sizing:border-box">
      @error('name')<div style="color:#a32d2d;font-size:12px;margin-top:-8px;margin-bottom:8px">{{ $message }}</div>@enderror

      <label style="display:block;font-size:13px;color:#18181b;margin-bottom:6px">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" required
        style="width:100%;padding:10px 12px;border:0.5px solid #e5e7eb;border-radius:8px;
               font-size:14px;margin-bottom:1rem;box-sizing:border-box">
      @error('email')<div style="color:#a32d2d;font-size:12px;margin-top:-8px;margin-bottom:8px">{{ $message }}</div>@enderror

      <label style="display:block;font-size:13px;color:#18181b;margin-bottom:6px">Company (optional)</label>
      <input type="text" name="company" value="{{ old('company') }}"
        style="width:100%;padding:10px 12px;border:0.5px solid #e5e7eb;border-radius:8px;
               font-size:14px;margin-bottom:1rem;box-sizing:border-box">

      <label style="display:block;font-size:13px;color:#18181b;margin-bottom:6px">Message</label>
      <textarea name="message" rows="5" required
        style="width:100%;padding:10px 12px;border:0.5px solid #e5e7eb;border-radius:8px;
               font-size:14px;margin-bottom:1.25rem;box-sizing:border-box;font-family:inherit;resize:vertical">{{ old('message') }}</textarea>
      @error('message')<div style="color:#a32d2d;font-size:12px;margin-top:-8px;margin-bottom:8px">{{ $message }}</div>@enderror

      <button type="submit"
        style="background:#18181b;color:#fff;border:none;padding:11px 24px;border-radius:8px;
               font-size:14px;font-weight:500;cursor:pointer">
        Send message
      </button>
    </form>
  </div>

</div>
@endsection
