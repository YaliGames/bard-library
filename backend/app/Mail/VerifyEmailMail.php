<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationUrl;
    public string $username;

    /**
     * Create a new message instance.
     */
    public function __construct(string $verificationUrl, string $username)
    {
        $this->verificationUrl = $verificationUrl;
        $this->username = $username;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('验证您的 Bard Library 邮箱')
            ->view('emails.verify_email')
            ->text('emails.verify_email_plain')
            ->with([
                'verificationUrl' => $this->verificationUrl,
                'username' => $this->username,
            ]);
    }
}
