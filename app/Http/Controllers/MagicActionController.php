<?php

namespace App\Http\Controllers;

use App\Models\IncrementReview;
use App\Services\MagicActionTokenService;
use Illuminate\Http\Request;

class MagicActionController extends Controller
{
    public function __construct(private MagicActionTokenService $tokenService) {}

    public function execute(Request $request)
    {
        $tokenStr = $request->query('token');
        if (!$tokenStr) {
            return response()->view('errors.404', [], 404);
        }

        $payload = $this->tokenService->validateToken($tokenStr);
        if (!$payload) {
            return view('actions.expired');
        }

        $actionType = $payload['action_type'];
        $params     = $payload['params'];

        if ($actionType === 'approve_increment') {
            $reviewId = $params['review_id'] ?? null;
            if ($reviewId) {
                $review = IncrementReview::find($reviewId);
                if ($review) {
                    $review->update([
                        'status'      => 'approved',
                        'approved_at' => now(),
                    ]);
                    return view('actions.success', [
                        'title'   => 'Increment Approved! 🎉',
                        'message' => "Salary increment for {$review->user->name} has been approved successfully.",
                    ]);
                }
            }
        }

        return redirect()->route('dashboard')->with('success', 'Action completed successfully!');
    }
}
