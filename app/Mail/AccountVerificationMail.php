<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $name;
    public string $purpose; // 'register' | 'activate'

    public function __construct(string $otp, string $name, string $purpose = 'activate')
    {
        $this->otp     = $otp;
        $this->name    = $name;
        $this->purpose = $purpose;
    }

    public function envelope(): Envelope
    {
        $subject = $this->purpose === 'register'
            ? 'Verifikasi Pendaftaran Akun - Perpustakaan Digital'
            : 'Verifikasi Aktivasi Akun - Perpustakaan Digital';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp_verification');
    }

    public function attachments(): array
    {
        return [];
    }
}
