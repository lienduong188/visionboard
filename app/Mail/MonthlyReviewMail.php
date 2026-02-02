<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $monthData
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📈 Monthly Review: {$this->monthData['month_name']} Summary",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.monthly-review',
            with: [
                'user' => $this->user,
                'data' => $this->monthData,
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
