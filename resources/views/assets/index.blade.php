@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="dash-page-header-0 mb-lg">
        <div>
            <span class="dash-role-badge">Mini ERP</span>
            <h1 class="heading-xl mb-xs">Asset & Device Vault 💻</h1>
            <p class="text-muted-sm">Track hardware, laptops, monitors, and access badges assigned to employees</p>
        </div>
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">{{ session('success') }}</div>
    @endif

    {{-- Asset Add Form --}}
    @if(auth()->user()->hasAnyRole(['admin', 'owner', 'super_admin', 'hr']))
    <div class="fv-card p-lg mb-lg">
        <div class="heading-md mb-md">Assign New Hardware / Asset</div>
        <form method="POST" action="{{ route('assets.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-md">
            @csrf
            <div>
                <label class="fv-label">Asset Name</label>
                <input type="text" name="asset_name" required placeholder="e.g. MacBook Pro M3" class="fv-input">
            </div>
            <div>
                <label class="fv-label">Category</label>
                <select name="category" class="fv-input">
                    <option value="Laptop">Laptop</option>
                    <option value="Monitor">Monitor</option>
                    <option value="Phone">Phone</option>
                    <option value="Badge">Access Badge</option>
                </select>
            </div>
            <div>
                <label class="fv-label">Serial Number</label>
                <input type="text" name="serial_number" placeholder="S/N C02X901" class="fv-input">
            </div>
            <div>
                <label class="fv-label">Assign To Employee</label>
                <select name="assigned_to" class="fv-input">
                    <option value="">Unassigned (Vault)</option>
                    @foreach($members as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary">+ Add Asset to Vault</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Assets Table --}}
    <div class="fv-card overflow-x-auto">
        <table class="fv-table">
            <thead>
                <tr>
                    <th>Asset Name</th>
                    <th>Category</th>
                    <th>Serial Number</th>
                    <th>Assigned To</th>
                    <th>Condition</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assets as $a)
                <tr>
                    <td class="font-semibold">{{ $a->asset_name }}</td>
                    <td><span class="fv-badge fv-badge-gray">{{ $a->category }}</span></td>
                    <td class="font-mono text-xs">{{ $a->serial_number ?? 'N/A' }}</td>
                    <td class="font-semibold text-emerald-400">{{ $a->assignedUser->name ?? 'Unassigned' }}</td>
                    <td>{{ $a->asset_condition }}</td>
                    <td><span class="fv-badge fv-badge-blue">{{ strtoupper($a->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
