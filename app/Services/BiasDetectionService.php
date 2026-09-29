<?php

namespace App\Services;

use App\Models\FeedbackBiasReport;
use App\Models\IncrementScore;
use App\Models\ManagerAccountabilityScore;
use App\Models\PerformanceFeedback;
use App\Models\PeerFeedback;

class BiasDetectionService
{
    private const BIAS_THRESHOLD = 20;

    public function analyze(PerformanceFeedback $feedback): FeedbackBiasReport
    {
        $period = $feedback->review_period;
        $year   = $feedback->review_year;

        $aiScore = IncrementScore::where('user_id', $feedback->employee_id)
            ->orderByDesc('score_month')
            ->value('final_score');

        $managerScore = $feedback->implied_score;

        $peerFeedbacks = PeerFeedback::where('employee_id', $feedback->employee_id)
            ->where('review_period', $period)
            ->where('review_year', $year)
            ->where('is_submitted', true)
            ->get();

        $peerCount = $peerFeedbacks->count();
        $peerScore = $peerCount > 0
            ? round($peerFeedbacks->avg(fn($p) => $p->average_score), 1)
            : null;

        $deviation = $aiScore ? abs($managerScore - $aiScore) : null;

        $history = PerformanceFeedback::where('manager_id', $feedback->manager_id)
            ->where('employee_id', $feedback->employee_id)
            ->where('status', '!=', 'draft')
            ->orderByDesc('review_year')
            ->orderByDesc('review_period')
            ->limit(4)
            ->get();

        $biasDetected   = false;
        $biasConfidence = 0;
        $biasType       = null;
        $biasReason     = '';
        $evidence       = [];

        if ($deviation !== null && $deviation >= self::BIAS_THRESHOLD) {
            $biasDetected    = true;
            $biasConfidence += 40;
            $biasType        = $managerScore < $aiScore ? 'consistent_low' : 'consistent_high';
            $biasReason      = "Manager score ({$managerScore}%) deviates {$deviation}% from AI objective score ({$aiScore}%).";
            $evidence['current_deviation'] = $deviation;
        }

        if ($history->count() >= 2) {
            $historicalScores = $history->map(fn($h) => $h->implied_score)->toArray();
            $allLow  = collect($historicalScores)->every(fn($s) => $s < 60);
            $allHigh = collect($historicalScores)->every(fn($s) => $s > 85);

            if ($allLow || $allHigh) {
                $biasDetected    = true;
                $biasConfidence += 30;
                $biasReason     .= " Pattern detected across {$history->count()} quarters.";
                $evidence['historical_scores'] = $historicalScores;
            }
        }

        if ($peerScore !== null && $deviation !== null) {
            $peerDeviation = abs($managerScore - $peerScore);
            if ($peerDeviation > 15) {
                $biasConfidence += 30;
                $biasReason     .= " Peer feedback ({$peerScore}%) also conflicts with manager assessment.";
                $evidence['peer_score']     = $peerScore;
                $evidence['peer_deviation'] = $peerDeviation;
            }
        }

        $biasConfidence = min(100, $biasConfidence);

        $report = FeedbackBiasReport::updateOrCreate(
            ['feedback_id' => $feedback->id],
            [
                'employee_id'          => $feedback->employee_id,
                'manager_id'           => $feedback->manager_id,
                'organization_id'      => $feedback->organization_id,
                'review_period'        => $period,
                'review_year'          => $year,
                'ai_score'             => $aiScore,
                'manager_implied_score'=> $managerScore,
                'peer_score'           => $peerScore,
                'peer_count'           => $peerCount,
                'deviation'            => $deviation,
                'bias_detected'        => $biasDetected,
                'bias_confidence'      => $biasConfidence,
                'bias_type'            => $biasType,
                'bias_reason'          => $biasReason ?: null,
                'evidence'             => $evidence ?: null,
            ]
        );

        if ($biasDetected) {
            $this->updateManagerAccountability(
                $feedback->manager_id,
                $feedback->organization_id,
                $period,
                $year,
                true
            );
        }

        return $report;
    }

    public function updateManagerAccountability(
        int $managerId,
        int $orgId,
        string $period,
        int $year,
        bool $addBiasFlag = false
    ): void {
        $record = ManagerAccountabilityScore::firstOrCreate(
            [
                'manager_id'      => $managerId,
                'organization_id' => $orgId,
                'review_period'   => $period,
                'review_year'     => $year,
            ],
            [
                'feedbacks_due'       => 0,
                'feedbacks_submitted' => 0,
                'feedbacks_on_time'   => 0,
                'bias_flags'          => 0,
                'accountability_score'=> 100,
            ]
        );

        if ($addBiasFlag) {
            $record->increment('bias_flags');
            $record->refresh();
        }

        $submissionRate = $record->feedbacks_due > 0
            ? ($record->feedbacks_submitted / $record->feedbacks_due) * 100
            : 100;

        $onTimeRate = $record->feedbacks_submitted > 0
            ? ($record->feedbacks_on_time / $record->feedbacks_submitted) * 100
            : 100;

        $biasPenalty = min(40, $record->bias_flags * 10);
        $score = round(
            ($submissionRate * 0.4) +
            ($onTimeRate * 0.4) +
            (max(0, 100 - $biasPenalty) * 0.2)
        );

        $record->update([
            'accountability_score' => $score,
            'breakdown' => [
                'submission_rate' => round($submissionRate),
                'on_time_rate'    => round($onTimeRate),
                'bias_flags'      => $record->bias_flags,
                'bias_penalty'    => $biasPenalty,
            ],
        ]);
    }
}
