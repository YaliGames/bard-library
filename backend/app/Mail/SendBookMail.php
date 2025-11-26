<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class SendBookMail extends Mailable
{
    use Queueable, SerializesModels;

    public $bookTitle;
    public $userName;
    public $filePath;
    public $fileName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $bookTitle, string $userName, string $filePath, string $fileName)
    {
        $this->bookTitle = $bookTitle;
        $this->userName = $userName;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【图书分享】{$this->bookTitle}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.send_book',
            text: 'emails.send_book_plain',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath)
                ->as($this->fileName)
        ];
    }
}
