<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public User $penerima,
        public string $type,
        public array $data
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->data['judul'] ?? 'Pemberitahuan Sistem SIGMA LAZ Al-Azhar';

        return new Envelope(
            subject: "[SIGMA] {$subject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reminder-notification',
        );
    }
}
