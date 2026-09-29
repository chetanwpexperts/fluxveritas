@extends('layouts.app')

@section('content')
<div class="page-container">
    <div class="dash-page-header-0 mb-lg">
        <div>
            <span class="dash-role-badge">Mini ERP</span>
            <h1 class="heading-xl mb-xs">Expense Claims 💳</h1>
            <p class="text-muted-sm">Submit travel, hardware, and client receipts for 1-click approvals</p>
        </div>
    </div>

    @if(session('success'))
    <div class="fv-alert fv-alert-success mb-md">{{ session('success') }}</div>
    @endif

    {{-- Expense Claim Form --}}
    <div class="fv-card p-lg mb-lg">
        <div class="heading-md mb-md">Submit Expense Claim</div>
        <form method="POST" action="{{ route('expenses.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-md">
            @csrf
            <div>
                <label class="fv-label">Title / Purpose</label>
                <input type="text" name="title" required placeholder="e.g. Client Lunch" class="fv-input">
            </div>
            <div>
                <label class="fv-label">Amount (₹)</label>
                <input type="number" name="amount" step="0.01" required placeholder="1500" class="fv-input">
            </div>
            <div>
                <label class="fv-label">Category</label>
                <select name="category" class="fv-input">
                    <option value="Travel">Travel</option>
                    <option value="Hardware">Hardware</option>
                    <option value="Software">Software</option>
                    <option value="Client">Client Expense</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="fv-btn fv-btn-primary w-full">+ Submit Claim</button>
            </div>
        </form>
    </div>

    {{-- Expense List Table --}}
    <div class="fv-card overflow-x-auto">
        <table class="fv-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $e)
                <tr>
                    <td>{{ $e->created_at->format('M j, Y') }}</td>
                    <td class="font-semibold">{{ $e->user->name ?? 'User' }}</td>
                    <td>{{ $e->title }}</td>
                    <td><span class="fv-badge fv-badge-gray">{{ $e->category }}</span></td>
                    <td class="font-bold">₹{{ number_format($e->amount, 2) }}</td>
                    <td>
                        @if($e->status === 'approved')
                        <span class="fv-badge fv-badge-green">APPROVED</span>
                        @else
                        <span class="fv-badge fv-badge-amber">PENDING</span>
                        @endif
                    </td>
                    <td>
                        @if($e->status === 'pending' && auth()->user()->hasAnyRole(['admin', 'owner', 'super_admin', 'hr']))
                        <form method="POST" action="{{ route('expenses.approve', $e->id) }}">
                            @csrf
                            <button type="submit" class="fv-btn fv-btn-primary btn-xs">Approve</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
