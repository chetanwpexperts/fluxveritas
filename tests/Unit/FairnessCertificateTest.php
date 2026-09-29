<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Services\FairnessCertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FairnessCertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_fairness_certificate_generation_and_verification(): void
    {
        $org = Organization::create([
            'name'           => 'Fairness Verified Corp',
            'slug'           => 'fairness-verified-corp',
            'status'         => 'active',
            'plan'           => 'pro',
            'billing_status' => 'active',
        ]);

        $service = new FairnessCertificateService();
        $token = $service->generateCertificateToken($org);

        $this->assertNotEmpty($token);

        $verification = $service->verifyCertificate($token);

        $this->assertNotNull($verification);
        $this->assertTrue($verification['is_valid']);
        $this->assertEquals($org->name, $verification['org_name']);
        $this->assertGreaterThanOrEqual(0, $verification['fairness_index']);
        $this->assertLessThanOrEqual(100, $verification['fairness_index']);
    }
}
