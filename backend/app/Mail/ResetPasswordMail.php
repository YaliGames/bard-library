<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetLink;
    public string $username;

    /**
     * Create a new message instance.
     */
    public function __construct(string $resetLink, string $username)
    {
        $this->resetLink = $resetLink;
        $this->username = $username;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('重置您的 Bard Library 密码')
            ->view('emails.reset_password')
            ->text('emails.reset_password_plain')
            ->with([
                'resetLink' => $this->resetLink,
                'username' => $this->username,
            ]);
    }
}
