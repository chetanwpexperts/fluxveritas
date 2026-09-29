<?php

namespace App\Http\Controllers;

use App\Models\FeedbackBiasReport;
use App\Models\ManagerAccountabilityScore;
use App\Models\PerformanceFeedback;
use App\Models\PeerFeedback;
use App\Models\User;
use App\Services\BiasDetectionService;
use App\Services\NotificationService;
use App\Services\PeerFeedbackService;
use Illuminate\Http\Request;

class PeerFeedbackController extends Controller
{
    private PeerFeedbackService $peerService;
    private BiasDetectionService $biasService;

    public function __construct()
    {
        $this->peerService = new PeerFeedbackService();
        $this->biasService = new BiasDetectionService();
    }

    /* ===== MANAGER: Trigger peer feedback collection ===== */

    public function requestPeerFeedback(Request $request, int $employeeId)
    {
        $user     = auth()->user();
        $orgId    = $user->organization_id;
        $period   = $this->getCurrentPeriod();
        $year     = now()->year;
        $employee = User::find($employeeId);

        $created = $this->peerService->createRequests($employeeId, $orgId, $period, $year);

        foreach ($created as $peerFeedback) {
            $peer = User::find($peerFeedback->reviewer_id);
            if (!$peer) continue;

            NotificationService::send(
                $peer->id,
                $orgId,
                'peer_feedback_request',
                '🤝 Peer Feedback Requested',
                "You've been selected to give anonymous feedback for {$employee?->name}. Takes less than 2 minutes.",
                '/peer-feedback/' . $peerFeedback->token,
                'Give Feedback',
                'normal',
                ['token' => $peerFeedback->token, 'employee_id' => $employeeId],
                $user->id
            );
        }

        $count = count($created);
        return back()->with('success',
            $count > 0
                ? "Peer feedback requested from {$count} colleague(s) for {$employee?->name}."
                : "Peer feedback requests already sent for {$employee?->name} this period."
        );
    }

    /* ===== PUBLIC: Anonymous peer feedback form (no login) ===== */

    public function showForm(string $token)
    {
        $peerFeedback = PeerFeedback::where('token', $token)
            ->where('is_submitted', false)
            ->firstOrFail();

        if ($peerFeedback->isExpired()) {
            return view('peer-feedback.expired');
        }

        $employee = $peerFeedback->employee;

        return view('peer-feedback.form', compact('peerFeedback', 'employee', 'token'));
    }

    /* ===== PUBLIC: Submit anonymous peer feedback (no login) ===== */

    public function submitForm(Request $request, string $token)
    {
        $peerFeedback = PeerFeedback::where('token', $token)
            ->where('is_submitted', false)
            ->firstOrFail();

        if ($peerFeedback->isExpired()) {
            return view('peer-feedback.expired');
        }

        $request->validate([
            'collaboration_score' => 'required|integer|between:1,5',
            'reliability_score'   => 'required|integer|between:1,5',
            'knowledge_score'     => 'required|integer|between:1,5',
            'helpfulness_score'   => 'required|integer|between:1,5',
            'strength'            => 'nullable|string|max:200',
            'improvement'         => 'nullable|string|max:200',
        ]);

        $peerFeedback->update([
            'collaboration_score' => $request->collaboration_score,
            'reliability_score'   => $request->reliability_score,
            'knowledge_score'     => $request->knowledge_score,
            'helpfulness_score'   => $request->helpfulness_score,
            'strength'            => $request->strength,
            'improvement'         => $request->improvement,
            'submitted_at'        => now(),
            'is_submitted'        => true,
        ]);

        // Re-run bias detection now that peer data exists
        $feedback = PerformanceFeedback::where('employee_id', $peerFeedback->employee_id)
            ->where('review_period', $peerFeedback->review_period)
            ->where('review_year', $peerFeedback->review_year)
            ->where('status', '!=', 'draft')
            ->first();

        if ($feedback) {
            $this->biasService->analyze($feedback);
        }

        return view('peer-feedback.thankyou');
    }

    /* ===== CEO/Admin: All bias reports ===== */

    public function biasReports()
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']), 403);

        $orgId  = auth()->user()->organization_id;
        $period = $this->getCurrentPeriod();
        $year   = now()->year;

        $biasReports = FeedbackBiasReport::where('organization_id', $orgId)
            ->where('review_period', $period)
            ->where('review_year', $year)
            ->with(['employee', 'manager', 'feedback'])
            ->orderByDesc('bias_confidence')
            ->get();

        $managerScores = ManagerAccountabilityScore::where('organization_id', $orgId)
            ->where('review_period', $period)
            ->where('review_year', $year)
            ->with('manager')
            ->orderBy('accountability_score')
            ->get();

        return view('feedback.bias-reports', compact(
            'biasReports', 'managerScores', 'period', 'year'
        ));
    }

    private function getCurrentPeriod(): string
    {
        $month = now()->month;
        if ($month <= 3)  return 'Q1';
        if ($month <= 6)  return 'Q2';
        if ($month <= 9)  return 'Q3';
        return 'Q4';
    }
}
