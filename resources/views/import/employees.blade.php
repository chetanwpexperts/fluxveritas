@extends('layouts.app')
@section('title', 'Import Employees')

@section('content')
<div style="max-width:760px;margin:0 auto;padding:2rem 1rem">

    <h1 style="font-size:22px;font-weight:500;color:#18181b;margin-bottom:4px">
        Import Employees
    </h1>
    <p style="font-size:13px;color:#6b7280;margin-bottom:1.5rem">
        Upload a file with your team's details. Departments and teams
        are created automatically. Everyone gets a link to set their password.
    </p>

    {{-- Role reference card --}}
    <div style="background:#f9fafb;border:0.5px solid #f3f4f6;
                border-radius:12px;padding:1.25rem;margin-bottom:1.5rem">
        <div style="font-size:13px;font-weight:500;color:#18181b;margin-bottom:10px">
            Roles you can assign
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:#6b7280">
            <div><strong style="color:#18181b">employee</strong> — regular team member, sees only own work</div>
            <div><strong style="color:#18181b">team_lead</strong> — leads one team, manages its members</div>
            <div><strong style="color:#18181b">manager</strong> — manages multiple teams + team leads</div>
            <div><strong style="color:#18181b">hr</strong> — people ops: employees, leaves, documents</div>
            <div><strong style="color:#18181b">admin</strong> — full org control (use sparingly)</div>
        </div>
    </div>

    {{-- Step 1: Download template --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;
                padding:1.5rem;margin-bottom:1.5rem">
        <div style="font-size:14px;font-weight:500;color:#18181b;margin-bottom:6px">
            Step 1 — Download the template
        </div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:1rem">
            Fill it with your employees. Columns: name, email, role,
            designation, department, team, reporting manager email,
            phone, employment type.
        </p>
        <div style="display:flex;gap:10px">
            <a href="{{ route('import.employees.template', ['format' => 'csv']) }}"
               style="background:#f3f4f6;color:#18181b;border:0.5px solid #e5e7eb;
                      padding:8px 16px;border-radius:8px;font-size:13px;text-decoration:none">
                Download CSV template
            </a>
            <a href="{{ route('import.employees.template', ['format' => 'xlsx']) }}"
               style="background:#f3f4f6;color:#18181b;border:0.5px solid #e5e7eb;
                      padding:8px 16px;border-radius:8px;font-size:13px;text-decoration:none">
                Download Excel template
            </a>
        </div>
    </div>

    {{-- Step 2: Upload / Paste JSON --}}
    <div style="background:#fff;border:0.5px solid #e5e7eb;border-radius:12px;padding:1.5rem">
        <div style="font-size:14px;font-weight:500;color:#18181b;margin-bottom:6px">
            Step 2 — Add your employees
        </div>
        <p style="font-size:13px;color:#6b7280;margin-bottom:1rem">
            Upload a CSV/Excel file, or paste JSON directly. You'll see a
            preview before anything is created.
        </p>

        @if(session('success'))
        <div style="background:#eaf3de;border:0.5px solid #c0dd97;color:#3b6d11;
                    padding:12px 14px;border-radius:10px;margin-bottom:1rem;font-size:13px">
            {{ session('success') }}
        </div>
        @endif
        @error('file')
        <div style="background:#fcebeb;border:0.5px solid #f7c1c1;color:#a32d2d;
                    padding:12px 14px;border-radius:10px;margin-bottom:1rem;font-size:13px">{{ $message }}</div>
        @enderror
        @error('json_text')
        <div style="background:#fcebeb;border:0.5px solid #f7c1c1;color:#a32d2d;
                    padding:12px 14px;border-radius:10px;margin-bottom:1rem;font-size:13px">{{ $message }}</div>
        @enderror

        {{-- ONE connected box: toggle tabs on top, input below --}}
        <div style="border:0.5px solid #e5e7eb;border-radius:12px;overflow:hidden">

            {{-- Toggle tabs --}}
            <div style="display:flex;border-bottom:0.5px solid #e5e7eb">
                <button type="button" id="tab-file"
                        style="flex:1;padding:12px;font-size:13px;font-weight:500;cursor:pointer;
                               background:#18181b;color:#fff;border:none">
                    Upload file
                </button>
                <button type="button" id="tab-json"
                        style="flex:1;padding:12px;font-size:13px;font-weight:500;cursor:pointer;
                               background:#fff;color:#6b7280;border:none;border-left:0.5px solid #e5e7eb">
                    Paste JSON
                </button>
            </div>

            {{-- Body --}}
            <div style="padding:1.25rem">

                {{-- FILE form --}}
                <form method="POST" action="{{ route('import.employees.preview') }}"
                      enctype="multipart/form-data" id="form-file">
                    @csrf
                    <input type="hidden" name="mode" value="file">
                    <input type="file" name="file" accept=".csv,.xlsx,.xls,.json"
                           style="display:block;margin-bottom:1rem;font-size:13px">
                    <button type="submit"
                            style="background:#18181b;color:#fff;border:none;padding:10px 20px;
                                   border-radius:8px;font-size:14px;font-weight:500;cursor:pointer">
                        Upload &amp; Preview
                    </button>
                </form>

                {{-- JSON form (hidden by default) --}}
                <form method="POST" action="{{ route('import.employees.preview') }}"
                      id="form-json" style="display:none">
                    @csrf
                    <input type="hidden" name="mode" value="json">

                    <div style="background:#f9fafb;border:0.5px solid #f3f4f6;border-radius:8px;
                                padding:12px;margin-bottom:10px">
                        <div style="font-size:12px;color:#6b7280;margin-bottom:6px">Example format:</div>
                        <pre style="font-size:11px;color:#18181b;margin:0;white-space:pre-wrap;font-family:monospace">[
  {
    "name": "Alex Kumar",
    "email": "alex@company.com",
    "role": "employee",
    "designation": "Frontend Developer",
    "department": "Engineering",
    "team": "Backend Team",
    "reporting_manager_email": "priya@company.com",
    "phone": "9876543210",
    "employment_type": "full_time"
  }
]</pre>
                    </div>

                    <textarea name="json_text" rows="10"
                              placeholder='[ { "name": "...", "email": "...", "role": "employee" } ]'
                              style="width:100%;padding:12px;border:0.5px solid #e5e7eb;border-radius:8px;
                                     font-size:12px;font-family:monospace;resize:vertical;margin-bottom:1rem;
                                     box-sizing:border-box">{{ old('json_text') }}</textarea>

                    <button type="submit"
                            style="background:#18181b;color:#fff;border:none;padding:10px 20px;
                                   border-radius:8px;font-size:14px;font-weight:500;cursor:pointer">
                        Preview JSON
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script>
    (function () {
        const tabFile  = document.getElementById('tab-file');
        const tabJson  = document.getElementById('tab-json');
        const formFile = document.getElementById('form-file');
        const formJson = document.getElementById('form-json');

        function activate(which) {
            const isFile = which === 'file';
            tabFile.style.background = isFile ? '#18181b' : '#fff';
            tabFile.style.color      = isFile ? '#fff'    : '#6b7280';
            tabJson.style.background = isFile ? '#fff'    : '#18181b';
            tabJson.style.color      = isFile ? '#6b7280' : '#fff';
            formFile.style.display   = isFile ? 'block'   : 'none';
            formJson.style.display   = isFile ? 'none'    : 'block';
        }

        tabFile.addEventListener('click', () => activate('file'));
        tabJson.addEventListener('click', () => activate('json'));

        @if(old('mode') === 'json' || $errors->has('json_text'))
        activate('json');
        @endif
    })();
    </script>

</div>
@endsection
