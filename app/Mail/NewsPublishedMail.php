<?php

namespace App\Mail;

use App\Models\News;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class NewsPublishedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $fileUrl;

    public function __construct(public News $news)
    {
        $this->fileUrl = $news->file_path
            ? url(Storage::disk('public')->url($news->file_path))
            : '';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📰 ' . $this->news->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.news-published',
        );
    }
}
