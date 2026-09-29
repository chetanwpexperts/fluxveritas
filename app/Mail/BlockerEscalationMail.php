<?php

namespace App\Mail;

use App\Models\Blocker;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BlockerEscalationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Blocker $blocker,
        public User $recipient,
        public int $daysOpen,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 Blocker Escalation — {$this->daysOpen} Days Unresolved",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.blocker-escalation',
            with: [
                'recipientName'    => $this->recipient->name,
                'blockerTitle'     => $this->blocker->title,
                'reportedBy'       => $this->blocker->blockedUser?->name ?? 'Unknown',
                'blockingPerson'   => $this->blocker->blockingUser?->name ?? $this->blocker->external_person_name ?? 'External',
                'priority'         => ucfirst($this->blocker->priority),
                'daysOpen'         => $this->daysOpen,
                'ownershipDisputed'=> $this->blocker->ownership_disputed ?? false,
                'blockerUrl'       => url('/dependencies/blocker/' . $this->blocker->id),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
