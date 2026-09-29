@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="dash-page-header-0 mb-lg">
        <div>
            <span class="dash-role-badge">Mini ERP</span>
            <h1 class="heading-xl mb-xs">Payroll Management 💵</h1>
            <p class="text-muted-sm">Itemized monthly salary statements driven by OutraqHQ Increment Engine</p>
        </div>
        <div class="dash-live-pill-green">
            <span class="live-dot"></span>
            <span class="dash-live-text-green font-bold">TOTAL PAYOUT: ₹{{ number_format($totalLiability) }}</span>
        </div>
    </div>

    <div class="fv-card p-lg mb-lg">
        <div class="flex-between flex-wrap gap-md">
            <div class="heading-md">Salary Breakdown ({{ $monthYear }})</div>
            <form method="GET" class="flex items-center gap-sm">
                <input type="month" name="month_year" value="{{ $monthYear }}" class="fv-input py-xs px-sm text-sm">
                <button type="submit" class="fv-btn fv-btn-primary btn-sm">Filter Month</button>
            </form>
        </div>
    </div>

    <div class="fv-card overflow-x-auto">
        <table class="fv-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Base Salary</th>
                    <th>OutraqHQ Increment %</th>
                    <th>Final Monthly Payout</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $p)
                <tr>
                    <td class="font-semibold">{{ $p->user->name ?? 'User #' . $p->user_id }}</td>
                    <td>₹{{ number_format($p->base_salary) }}</td>
                    <td><span class="fv-badge fv-badge-green">+{{ $p->increment_pct }}%</span></td>
                    <td class="font-bold text-emerald-400">₹{{ number_format($p->final_salary) }}</td>
                    <td><span class="fv-badge fv-badge-blue">{{ strtoupper($p->status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
