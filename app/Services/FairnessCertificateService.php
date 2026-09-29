<?php

namespace App\Services;

use App\Models\FairnessFlag;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class FairnessCertificateService
{
    /**
     * Generate a cryptographically signed Fair Workplace Verification Token
     */
    public function generateCertificateToken(Organization $org): string
    {
        $payload = [
            'org_id'       => $org->id,
            'org_name'     => $org->name,
            'plan'         => $org->plan,
            'issued_at'    => now()->timestamp,
            'valid_until'  => now()->addYear()->timestamp,
        ];

        return Crypt::encrypt(json_encode($payload));
    }

    /**
     * Verify a public cryptographic certificate token and calculate fairness rating
     */
    public function verifyCertificate(string $tokenStr): ?array
    {
        try {
            $payload = json_decode(Crypt::decrypt($tokenStr), true);
            $orgId   = $payload['org_id'] ?? null;

            if (!$orgId) return null;

            $org = Organization::find($orgId);
            // Only organizations that chose to publish a certificate, and are active and real
            if (!$org || !$this->isPublished($org)) return null;

            $pendingFlags = FairnessFlag::where('organization_id', $orgId)->where('status', 'pending')->count();
            $resolvedFlags= FairnessFlag::where('organization_id', $orgId)->where('status', 'confirmed')->count();

            // Calculate Fairness Index (0-100%)
            $fairnessIndex = max(0, min(100, 100 - ($pendingFlags * 5)));

            return [
                'is_valid'         => true,
                'org_name'         => $org->name,
                'org_slug'         => $org->slug,
                'fairness_index'   => $fairnessIndex,
                'status_label'     => $fairnessIndex >= 90 ? 'Few open fairness flags' : 'Fairness flags under review',
                'audited_at'       => now()->toFormattedDateString(),
                'signature_hash'   => hash('sha256', $tokenStr),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /** Publishing is opt-in: organizations.settings.fairness_certificate_public = true. */
    public function isPublished(Organization $org): bool
    {
        return ($org->settings['fairness_certificate_public'] ?? false) === true
            && $org->status === Organization::STATUS_ACTIVE
            && !$org->is_demo;
    }
}
