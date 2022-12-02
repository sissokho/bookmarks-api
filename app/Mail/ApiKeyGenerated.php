<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApiKeyGenerated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $userName, public string $apiKey)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bookmarks API: Api key',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.api-key-generated',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
