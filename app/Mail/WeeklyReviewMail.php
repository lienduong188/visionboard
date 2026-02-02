<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $weekData
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📊 Weekly Review: Your Vision Board Summary ({$this->weekData['week_start']} - {$this->weekData['week_end']})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-review',
            with: [
                'user' => $this->user,
                'data' => $this->weekData,
                'goalsUrl' => route('goals.index'),
                'analyticsUrl' => route('analytics.index'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
