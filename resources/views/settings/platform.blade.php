@extends('layouts.app')
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- Platform Info --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Platform Information</h2>
                </div>
                <div class="p-lg">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        @foreach([
                            ['Platform Name',         'OutraqHQ / OutraqHQ'],
                            ['Version',               'v1.0.0-beta'],
                            ['Total Organizations',   App\Models\Organization::count()],
                            ['Total Users',           App\Models\User::count()],
                            ['Environment',           config('app.env')],
                            ['PHP Version',           PHP_VERSION],
                        ] as [$label, $value])
                        <div>
                            <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#71717a;margin-bottom:4px;">{{ $label }}</div>
                            <div style="font-size:0.875rem;font-weight:600;color:#09090b;">{{ $value }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Platform Stats --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Platform Statistics</h2>
                </div>
                <div class="p-lg">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
                        @foreach([
                            ['Total Activities',  App\Models\Activity::count()],
                            ['Fairness Flags',    App\Models\FairnessFlag::count()],
                            ['Open Blockers',     App\Models\Blocker::where('status','open')->count()],
                            ['Active Projects',   App\Models\Project::where('status','active')->count()],
                            ['Free Plan Orgs',    App\Models\Organization::where('plan','free')->count()],
                            ['Pro Plan Orgs',     App\Models\Organization::where('plan','pro')->count()],
                        ] as [$label, $value])
                        <div style="background:#fafafa;border:1px solid #e4e4e7;border-radius:8px;padding:16px;">
                            <div style="font-size:1.5rem;font-weight:800;color:#09090b;">{{ $value }}</div>
                            <div style="font-size:0.75rem;color:#71717a;margin-top:4px;">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

    </div>

</div>
@endsection
