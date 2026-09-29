<?php

namespace App\Http\Controllers;

use App\Models\EmployeeStatement;
use App\Models\IncrementScore;
use App\Models\PerformanceFeedback;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /* ===== MANAGER: Team feedback overview ===== */

    public function index()
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $currentPeriod = $this->getCurrentPeriod();
        $currentYear   = now()->year;

        if ($user->hasAnyRole(['admin', 'owner', 'ceo'])) {
            $teamMembers = User::where('organization_id', $orgId)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->whereHas('roles', fn($q) => $q->whereIn('name', [
                    'admin', 'manager', 'team_lead', 'employee',
                ]))
                ->with('department')
                ->orderBy('name')
                ->get();
        } else {
            $ids = $user->manageableUserIds()->filter(fn($id) => $id !== $user->id);
            $teamMembers = User::where('organization_id', $orgId)
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->with('department')
                ->orderBy('name')
                ->get();
        }

        $existingFeedbacks = PerformanceFeedback::where('manager_id', $user->id)
            ->where('review_period', $currentPeriod)
            ->where('review_year', $currentYear)
            ->get()
            ->keyBy('employee_id');

        return view('feedback.index', compact(
            'teamMembers', 'existingFeedbacks', 'currentPeriod', 'currentYear'
        ));
    }

    /* ===== MANAGER: Create/edit feedback form ===== */

    public function create(int $employeeId)
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'owner', 'ceo'])) {
            $employee = User::where('organization_id', $user->organization_id)
                ->where('id', '!=', $user->id)
                ->findOrFail($employeeId);
        } else {
            $ids = $user->manageableUserIds()->filter(fn($id) => $id !== $user->id);
            $employee = User::where('organization_id', $user->organization_id)
                ->whereIn('id', $ids)
                ->findOrFail($employeeId);
        }

        $currentPeriod = $this->getCurrentPeriod();
        $currentYear   = now()->year;

        $existing = PerformanceFeedback::where('employee_id', $employeeId)
            ->where('manager_id', $user->id)
            ->where('review_period', $currentPeriod)
            ->where('review_year', $currentYear)
            ->first();

        if ($existing && $existing->status === 'submitted') {
            return redirect()->route('feedback.index')
                ->with('info', 'Feedback for this period has already been submitted.');
        }

        return view('feedback.create', compact(
            'employee', 'currentPeriod', 'currentYear', 'existing'
        ));
    }

    /* ===== MANAGER: Store/update feedback ===== */

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'employee_id'           => 'required|exists:users,id',
            'delivery_score'        => 'required|integer|between:1,4',
            'timeliness_score'      => 'required|integer|between:1,4',
            'availability_score'    => 'required|integer|between:1,4',
            'collaboration_score'   => 'required|integer|between:1,4',
            'notable_achievement'   => 'nullable|string|max:300',
            'area_of_improvement'   => 'nullable|string|max:300',
            'special_circumstances' => 'nullable|string|max:200',
            'manager_confirmed'     => 'required|accepted',
            'action'                => 'required|in:draft,submit',
        ]);

        $currentPeriod = $this->getCurrentPeriod();
        $currentYear   = now()->year;
        $status        = $validated['action'] === 'submit' ? 'submitted' : 'draft';

        $feedback = PerformanceFeedback::updateOrCreate(
            [
                'employee_id'   => $validated['employee_id'],
                'manager_id'    => $user->id,
                'review_period' => $currentPeriod,
                'review_year'   => $currentYear,
            ],
            [
                'organization_id'       => $user->organization_id,
                'delivery_score'        => $validated['delivery_score'],
                'timeliness_score'      => $validated['timeliness_score'],
                'availability_score'    => $validated['availability_score'],
                'collaboration_score'   => $validated['collaboration_score'],
                'notable_achievement'   => $validated['notable_achievement'],
                'area_of_improvement'   => $validated['area_of_improvement'],
                'special_circumstances' => $validated['special_circumstances'],
                'manager_confirmed'     => true,
                'status'                => $status,
                'submitted_at'          => $status === 'submitted' ? now() : null,
                'deadline'              => now()->addDays(7),
            ]
        );

        if ($status === 'submitted') {
            $employee = User::find($validated['employee_id']);

            NotificationService::send(
                $validated['employee_id'],
                $user->organization_id,
                'feedback_submitted',
                '📋 Your Performance Feedback is Ready',
                $user->name . ' has submitted your ' . $currentPeriod . ' ' . $currentYear . ' performance feedback. You have 5 days to add your statement.',
                '/feedback/my/' . $feedback->id,
                'View & Respond',
                'normal',
                ['feedback_id' => $feedback->id],
                $user->id
            );

            NotificationService::sendToManagers(
                $user->organization_id,
                'feedback_ready_for_review',
                '📊 Feedback Submitted for ' . $employee?->name,
                $user->name . ' submitted ' . $currentPeriod . ' feedback for ' . $employee?->name . '. Ready for CEO review.',
                '/feedback/admin',
                'low',
                ['feedback_id' => $feedback->id],
                $user->id
            );

            // Run bias detection
            $biasService = new \App\Services\BiasDetectionService();
            $biasReport  = $biasService->analyze($feedback);

            if ($biasReport->bias_detected) {
                NotificationService::sendToManagers(
                    $user->organization_id,
                    'bias_detected',
                    '⚠️ Potential Bias Detected',
                    "Manager feedback for {$employee?->name} shows {$biasReport->bias_confidence}% confidence of bias. Review in Bias Reports.",
                    '/feedback/bias-reports',
                    'high',
                    ['bias_report_id' => $biasReport->id],
                    $user->id
                );
            }

            // Update manager accountability record
            $biasService->updateManagerAccountability(
                $user->id,
                $user->organization_id,
                $currentPeriod,
                $currentYear
            );

            // Auto-trigger peer feedback requests
            $peerService = new \App\Services\PeerFeedbackService();
            $peerService->createRequests(
                $validated['employee_id'],
                $user->organization_id,
                $currentPeriod,
                $currentYear
            );

            return redirect()->route('feedback.index')
                ->with('success', 'Feedback submitted for ' . $employee?->name . '!');
        }

        return redirect()->route('feedback.index')
            ->with('success', 'Feedback saved as draft.');
    }

    /* ===== EMPLOYEE: List received feedbacks ===== */

    public function myFeedback()
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if ($user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            return redirect()->route('feedback.admin');
        }

        $myFeedbacks = PerformanceFeedback::where('employee_id', $user->id)
            ->where('status', '!=', 'draft')
            ->with(['manager', 'statement'])
            ->orderByDesc('review_year')
            ->orderByDesc('created_at')
            ->get();

        $orgFeedbacks = PerformanceFeedback::where('organization_id', $orgId)
            ->where('status', '!=', 'draft')
            ->where('employee_id', '!=', $user->id)
            ->with(['employee', 'manager'])
            ->orderByDesc('review_year')
            ->orderByDesc('created_at')
            ->get();

        $currentPeriod = $this->getCurrentPeriod();
        $currentYear   = now()->year;

        return view('feedback.my', compact(
            'myFeedbacks', 'orgFeedbacks', 'currentPeriod', 'currentYear'
        ));
    }

    /* ===== EMPLOYEE: View single feedback + add statement ===== */

    public function show(int $feedbackId)
    {
        $user     = auth()->user();
        $feedback = PerformanceFeedback::where('employee_id', $user->id)
            ->with(['manager', 'statement'])
            ->findOrFail($feedbackId);

        if (!$feedback->employee_viewed_at) {
            $feedback->update([
                'employee_viewed_at' => now(),
                'status'             => 'employee_reviewed',
            ]);
        }

        $hasStatement = $feedback->statement !== null;

        return view('feedback.show', compact('feedback', 'hasStatement'));
    }

    /* ===== EMPLOYEE: Submit statement ===== */

    public function storeStatement(Request $request, int $feedbackId)
    {
        $user     = auth()->user();
        $feedback = PerformanceFeedback::where('employee_id', $user->id)->findOrFail($feedbackId);

        $request->validate([
            'statement' => 'required|string|min:20|max:1000',
        ]);

        EmployeeStatement::updateOrCreate(
            ['feedback_id' => $feedbackId, 'user_id' => $user->id],
            ['statement' => $request->statement, 'submitted_at' => now()]
        );

        NotificationService::send(
            $feedback->manager_id,
            $user->organization_id,
            'employee_statement_added',
            '💬 Employee Added Statement',
            $user->name . ' added their statement to their ' . $feedback->review_period . ' feedback.',
            '/feedback/admin/' . $feedbackId,
            'View Statement',
            'low',
            ['feedback_id' => $feedbackId],
            $user->id
        );

        return back()->with('success', 'Your statement has been submitted.');
    }

    /* ===== ADMIN/CEO: All feedbacks overview ===== */

    public function adminIndex()
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']), 403);

        $orgId   = auth()->user()->organization_id;
        $period  = $this->getCurrentPeriod();
        $year    = now()->year;

        $feedbacks = PerformanceFeedback::where('organization_id', $orgId)
            ->where('review_period', $period)
            ->where('review_year', $year)
            ->with(['employee', 'manager', 'statement'])
            ->orderByDesc('submitted_at')
            ->get();

        $totalEmployees = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
            ->count();

        $submitted     = $feedbacks->where('status', '!=', 'draft')->count();
        $withStatement = $feedbacks->filter(fn($f) => $f->statement !== null)->count();

        $pendingManagers = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'team_lead']))
            ->whereDoesntHave('managedFeedbacks', fn($q) =>
                $q->where('review_period', $period)
                  ->where('review_year', $year)
                  ->where('status', '!=', 'draft')
            )
            ->count();

        return view('feedback.admin', compact(
            'feedbacks', 'period', 'year',
            'totalEmployees', 'submitted', 'withStatement', 'pendingManagers'
        ));
    }

    /* ===== ADMIN/CEO: Single feedback detail ===== */

    public function adminShow(int $feedbackId)
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']), 403);

        $orgId    = auth()->user()->organization_id;
        $feedback = PerformanceFeedback::where('organization_id', $orgId)
            ->with(['employee', 'manager', 'statement'])
            ->findOrFail($feedbackId);

        $aiScore = IncrementScore::where('user_id', $feedback->employee_id)
            ->orderByDesc('score_month')
            ->value('final_score');

        $managerImpliedScore = $feedback->implied_score;
        $deviation           = $aiScore ? abs($managerImpliedScore - $aiScore) : null;
        $biasFlag            = $deviation && $deviation > 20;

        return view('feedback.admin-show', compact(
            'feedback', 'aiScore', 'managerImpliedScore', 'deviation', 'biasFlag'
        ));
    }

    /* ===== Helper ===== */

    private function getCurrentPeriod(): string
    {
        $month = now()->month;
        if ($month <= 3)  return 'Q1';
        if ($month <= 6)  return 'Q2';
        if ($month <= 9)  return 'Q3';
        return 'Q4';
    }
}
