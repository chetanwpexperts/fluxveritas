<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailDigestService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    public function ceoDashboard()
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']), 403);

        $orgId = auth()->user()->organization_id;
        $data  = $this->reportService->getCeoDailyDigest($orgId);

        return view('reports.ceo-dashboard', compact('data'));
    }

    public function sendCeoDigestNow()
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']), 403);

        (new EmailDigestService())->sendCeoDailyDigests();

        return back()->with('success', 'CEO daily digest sent successfully!');
    }

    public function employeeReport(Request $request, int $userId = null)
    {
        $authUser = auth()->user();

        if ($userId) {
            if ($authUser->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
                $employee = User::where('organization_id', $authUser->organization_id)
                    ->findOrFail($userId);
            } elseif ($authUser->hasAnyRole(['manager', 'team_lead'])) {
                if (!$authUser->manageableUserIds()->contains($userId)) {
                    abort(403, 'You can only view reports for your own team.');
                }
                $employee = User::where('organization_id', $authUser->organization_id)
                    ->findOrFail($userId);
            } else {
                if ($userId != $authUser->id) {
                    abort(403);
                }
                $employee = $authUser;
            }
        } else {
            $employee = $authUser;
        }

        $period = $request->get('period', $this->getCurrentPeriod());
        $year   = (int) $request->get('year', now()->year);
        $report = $this->reportService->getEmployeeQuarterlyReport($employee, $period, $year);

        return view('reports.employee-report', compact('report', 'period', 'year'));
    }

    public function teamReports(Request $request)
    {
        $authUser = auth()->user();

        abort_if(!$authUser->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'manager']), 403);

        $orgId  = $authUser->organization_id;
        $period = $request->get('period', $this->getCurrentPeriod());
        $year   = (int) $request->get('year', now()->year);

        if ($authUser->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            $employees = User::where('organization_id', $orgId)
                ->where('is_active', true)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['employee', 'team_lead', 'admin']))
                ->with('department')
                ->orderBy('name')
                ->get();
        } else {
            $reportIds = $authUser->manageableUserIds();
            $employees = User::whereIn('id', $reportIds)
                ->where('is_active', true)
                ->with('department')
                ->orderBy('name')
                ->get();
        }

        $reports = $employees->map(fn($emp) =>
            $this->reportService->getEmployeeQuarterlyReport($emp, $period, $year)
        );

        return view('reports.team', compact('reports', 'period', 'year'));
    }

    private function getCurrentPeriod(): string
    {
        $m = now()->month;
        if ($m <= 3) return 'Q1';
        if ($m <= 6) return 'Q2';
        if ($m <= 9) return 'Q3';
        return 'Q4';
    }
}
