<?php
namespace App\Mail;

use App\Models\AdminUser;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdminPasswordResetMail extends Mailable
{
    public function __construct(
        public AdminUser $admin,
        public string $resetUrl,
        public int $expiresInMinutes
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset your administrator password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.password-reset',
        );
    }
}
