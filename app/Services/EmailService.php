<?php

namespace App\Services;

use App\Mail\AgentEmailMail;
use App\Mail\BlockerEscalationMail;
use App\Mail\DailyWorkLogReminderMail;
use App\Mail\FairnessFlagAlertMail;
use App\Mail\TeamInvitationMail;
use App\Mail\WeeklyDigestMail;
use App\Mail\WelcomeMail;
use App\Models\Blocker;
use App\Models\FairnessFlag;
use App\Models\Organization;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendInvitation(TeamInvitation $invitation, Organization $org, string $inviterName): bool
    {
        try {
            Mail::to($invitation->email)->send(new TeamInvitationMail($invitation, $org, $inviterName));
            Log::info("Invitation sent to {$invitation->email}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send invitation: {$e->getMessage()}");
            return false;
        }
    }

    public function sendWelcome(User $user, Organization $org): bool
    {
        try {
            Mail::to($user->email)->send(new WelcomeMail($user, $org));
            return true;
        } catch (\Exception $e) {
            Log::error("Welcome email failed: {$e->getMessage()}");
            return false;
        }
    }

    public function sendFairnessAlert(FairnessFlag $flag, User $recipient): bool
    {
        try {
            Mail::to($recipient->email)->send(new FairnessFlagAlertMail($flag, $recipient));
            return true;
        } catch (\Exception $e) {
            Log::error("Fairness alert failed: {$e->getMessage()}");
            return false;
        }
    }

    public function sendBlockerEscalation(Blocker $blocker, User $recipient, int $daysOpen): bool
    {
        try {
            Mail::to($recipient->email)->send(new BlockerEscalationMail($blocker, $recipient, $daysOpen));
            return true;
        } catch (\Exception $e) {
            Log::error("Blocker email failed: {$e->getMessage()}");
            return false;
        }
    }

    public function sendWorkLogReminder(User $user): bool
    {
        try {
            Mail::to($user->email)->send(new DailyWorkLogReminderMail($user));
            return true;
        } catch (\Exception $e) {
            Log::error("Work log reminder failed: {$e->getMessage()}");
            return false;
        }
    }

    public function sendWeeklyDigest(User $recipient, Organization $org, array $stats): bool
    {
        try {
            Mail::to($recipient->email)->send(new WeeklyDigestMail($recipient, $org, $stats));
            return true;
        } catch (\Exception $e) {
            Log::error("Weekly digest failed: {$e->getMessage()}");
            return false;
        }
    }

    public function sendAgentEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $body,
        string $actionUrl = '',
        string $actionLabel = ''
    ): bool {
        try {
            Mail::to($toEmail)->send(new AgentEmailMail(
                emailRecipientName: $toName,
                emailSubject: $subject,
                emailBody: $body,
                emailActionUrl: $actionUrl,
                emailActionLabel: $actionLabel,
            ));
            Log::info("Agent email sent to {$toEmail}: {$subject}");
            return true;
        } catch (\Exception $e) {
            Log::error("Agent email failed: {$e->getMessage()}");
            return false;
        }
    }

    public function sendBulkAgentEmail(array $recipients, string $subject, string $body): array
    {
        $results = [];
        foreach ($recipients as $recipient) {
            $sent = $this->sendAgentEmail(
                $recipient['email'],
                $recipient['name'],
                $subject,
                $body
            );
            $results[] = [
                'email' => $recipient['email'],
                'name'  => $recipient['name'],
                'sent'  => $sent,
            ];
        }
        return $results;
    }
}
