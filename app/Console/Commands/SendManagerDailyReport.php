<?php

namespace App\Console\Commands;

use App\Services\EmailDigestService;
use Illuminate\Console\Command;

class SendManagerDailyReport extends Command
{
    protected $signature   = 'digest:managers';
    protected $description = 'Send daily team reports to all managers';

    public function handle(): void
    {
        $this->info('Sending manager daily reports...');
        (new EmailDigestService())->sendManagerDailyReports();
        $this->info('Manager daily reports sent successfully.');
    }
}
