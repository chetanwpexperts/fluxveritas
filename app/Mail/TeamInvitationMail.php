<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TeamInvitation $invitation,
        public Organization $organization,
        public string $inviterName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited to join {$this->organization->name} on OutraqHQ",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.team-invitation',
            with: [
                'inviteUrl'   => url('/team/accept/' . $this->invitation->token),
                'orgName'     => $this->organization->name,
                'inviterName' => $this->inviterName,
                'role'        => ucfirst($this->invitation->role),
                'expiresAt'   => $this->invitation->expires_at?->format('M j, Y'),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
