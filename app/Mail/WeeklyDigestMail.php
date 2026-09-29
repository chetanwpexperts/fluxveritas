<?php

namespace App\Mail;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public Organization $org,
        public array $stats,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📊 Your Weekly Team Report — ' . now()->format('M j'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-digest',
            with: [
                'recipientName' => $this->recipient->name,
                'weekStart'     => now()->startOfWeek()->format('M j'),
                'weekEnd'       => now()->endOfWeek()->format('M j, Y'),
                'stats'         => $this->stats,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
