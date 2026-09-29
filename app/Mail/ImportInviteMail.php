<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to people added by an employee import, with a link to set their password. */
class ImportInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Organization $organization,
        public string $setPasswordUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "You've been added to {$this->organization->name} on OutraqHQ");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.import-invite',
            with: [
                'userName' => $this->user->name,
                'orgName'  => $this->organization->name,
                'url'      => $this->setPasswordUrl,
                'days'     => \App\Http\Controllers\ImportInviteController::VALID_DAYS,
            ],
        );
    }
}
