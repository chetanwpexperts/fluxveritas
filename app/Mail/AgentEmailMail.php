<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgentEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailRecipientName,
        public string $emailSubject,
        public string $emailBody,
        public string $emailActionUrl = '',
        public string $emailActionLabel = '',
        public string $emailSenderContext = 'OutraqHQ AI Agent',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.agent-email',
            with: [
                'recipientName' => $this->emailRecipientName,
                'subject'       => $this->emailSubject,
                'body'          => $this->emailBody,
                'actionUrl'     => $this->emailActionUrl,
                'actionLabel'   => $this->emailActionLabel,
                'senderContext' => $this->emailSenderContext,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
