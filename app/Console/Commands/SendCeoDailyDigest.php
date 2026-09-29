<?php

namespace App\Console\Commands;

use App\Services\EmailDigestService;
use Illuminate\Console\Command;

class SendCeoDailyDigest extends Command
{
    protected $signature   = 'digest:ceo';
    protected $description = 'Send CEO daily digest emails to all organizations';

    public function handle(): void
    {
        $this->info('Sending CEO daily digests...');
        (new EmailDigestService())->sendCeoDailyDigests();
        $this->info('CEO daily digests sent successfully.');
    }
}
