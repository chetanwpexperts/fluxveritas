<?php

namespace App\Http\Controllers;

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
}
