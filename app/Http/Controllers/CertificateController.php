<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\FairnessCertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(private FairnessCertificateService $certificateService) {}

    public function verify(Request $request, string $token)
    {
        $verification = $this->certificateService->verifyCertificate($token);

        if (!$verification) {
            return view('certificate.invalid');
        }

        return view('certificate.verify', compact('verification'));
    }

    public function badge(int $orgId)
    {
        $org = Organization::findOrFail($orgId);
        $token = $this->certificateService->generateCertificateToken($org);
        $verifyUrl = route('fairness.verify', ['token' => $token]);

        return view('certificate.badge', compact('org', 'token', 'verifyUrl'));
    }
}
