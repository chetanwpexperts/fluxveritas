<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\FairnessEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeOrganizationFairnessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $organizationId) {}

    public function handle(FairnessEngine $engine): void
    {
        $org = Organization::find($this->organizationId);
        if ($org) {
            $engine->runAudit($org);
        }
    }
}
