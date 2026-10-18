<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordRecoveryCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recoveryCode,
        public readonly string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Código para recuperar su contraseña');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-recovery-code');
    }

    public function attachments(): array
    {
        return [];
    }
}
