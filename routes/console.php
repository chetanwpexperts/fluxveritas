<?php

use App\Models\Organization;
use App\Services\AutonomousAgent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run full agent (team checks + health + cache clear) every hour
Schedule::command('agent:run')
    ->hourly()
    ->withoutOverlapping();

// Dedicated system health check every 30 minutes
Schedule::call(function () {
    $agent = new AutonomousAgent();
    $agent->checkSystemHealth();
})->everyThirtyMinutes()->name('health-check')->withoutOverlapping();

// Security scan daily at midnight
Schedule::call(function () {
    $agent = new AutonomousAgent();
    $agent->runSecurityScan();
})->daily()->at('00:00')->name('security-scan')->withoutOverlapping();

// Clear stale AI/GitHub caches every 2 hours
Schedule::call(function () {
    $agent = new AutonomousAgent();
    $agent->clearStaleCache();
})->everyTwoHours()->name('cache-clear')->withoutOverlapping();

// Weekly fairness check — Monday 08:30
Schedule::call(function () {
    $orgs  = Organization::where('status', 'active')->get();
    $agent = new AutonomousAgent();
    foreach ($orgs as $org) {
        $agent->runFairnessCheck($org);
    }
})->weekly()->mondays()->at('08:30')->name('fairness-check')->withoutOverlapping();

// CEO daily digest — 03:30 IST
Schedule::command('digest:ceo')
    ->dailyAt('03:30')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

// Manager daily team report — 01:30 IST
Schedule::command('digest:managers')
    ->dailyAt('01:30')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

// Daily organization plan expiration check — 00:05 IST
Schedule::command('org:check-expirations')
    ->dailyAt('00:05')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

