<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $orgId = $user->organization_id;

        $query = Expense::where('organization_id', $orgId)->with(['user', 'approver']);

        if (!$user->hasAnyRole(['admin', 'owner', 'super_admin', 'hr'])) {
            $query->where('user_id', $user->id);
        }

        $expenses = $query->latest()->get();

        return view('expenses.index', compact('expenses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'amount'   => 'required|numeric|min:1',
            'category' => 'required|string',
        ]);

        $user = auth()->user();

        Expense::create([
            'organization_id' => $user->organization_id,
            'user_id'         => $user->id,
            'title'           => $validated['title'],
            'amount'          => $validated['amount'],
            'category'        => $validated['category'],
            'status'          => 'pending',
        ]);

        return back()->with('success', 'Expense claim submitted successfully for approval!');
    }

    public function approve(int $id)
    {
        $expense = Expense::findOrFail($id);
        $expense->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Expense claim approved!');
    }
}
