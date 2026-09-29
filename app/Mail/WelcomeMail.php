<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Organization $organization,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to OutraqHQ — Let's get you set up! 🚀",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome',
            with: [
                'userName'  => $this->user->name,
                'orgName'   => $this->organization->name,
                'userRole'  => ucfirst($this->user->role ?? 'Employee'),
                'userEmail' => $this->user->email,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
