<?php

namespace App\Http\Controllers;

use App\Models\IncrementReview;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = auth()->user()->organization_id;
        $monthYear = $request->get('month_year', now()->format('Y-m'));

        $users = User::where('organization_id', $orgId)->where('is_active', true)->get();

        foreach ($users as $user) {
            $latestReview = IncrementReview::where('user_id', $user->id)
                ->where('status', 'approved')
                ->latest()
                ->first();

            $incrementPct = $latestReview ? $latestReview->recommended_increment_pct : 0.00;
            $baseSalary = 50000.00; // Base default benchmark
            $finalSalary = $baseSalary + ($baseSalary * ($incrementPct / 100));

            Payroll::firstOrCreate(
                ['organization_id' => $orgId, 'user_id' => $user->id, 'month_year' => $monthYear],
                [
                    'base_salary'   => $baseSalary,
                    'increment_pct' => $incrementPct,
                    'final_salary'  => $finalSalary,
                    'status'        => 'processed',
                    'paid_at'       => now(),
                ]
            );
        }

        $payrolls = Payroll::where('organization_id', $orgId)
            ->where('month_year', $monthYear)
            ->with('user')
            ->get();

        $totalLiability = $payrolls->sum('final_salary');

        return view('payroll.index', compact('payrolls', 'monthYear', 'totalLiability'));
    }
}
