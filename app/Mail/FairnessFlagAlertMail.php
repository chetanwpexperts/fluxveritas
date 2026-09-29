<?php

namespace App\Mail;

use App\Models\FairnessFlag;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FairnessFlagAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FairnessFlag $flag,
        public User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚖️ Fairness Alert — Review Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.fairness-flag',
            with: [
                'recipientName' => $this->recipient->name,
                'flagType'      => ucwords(str_replace('_', ' ', $this->flag->flag_type)),
                'confidence'    => round($this->flag->confidence_score * 100),
                'description'   => $this->flag->evidence['description'] ?? 'See details in system',
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
