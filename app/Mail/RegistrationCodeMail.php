<?php

namespace App\Mail;

use App\Services\Auth\RegistrationCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->code . ' est ton code NAKAMANET',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-code',
            text: 'emails.registration-code-text',
            with: [
                'code'       => $this->code,
                'ttlMinutes' => RegistrationCodeService::TTL_MINUTES,
            ],
        );
    }
}
