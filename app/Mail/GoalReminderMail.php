<?php

namespace App\Mail;

use App\Models\Goal;
use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GoalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Goal $goal,
        public Reminder $reminder
    ) {}

    public function envelope(): Envelope
    {
        $subject = match($this->reminder->type) {
            'deadline' => "⏰ Deadline Reminder: {$this->goal->title}",
            'progress' => "📊 Progress Check: {$this->goal->title}",
            default => "🔔 Reminder: {$this->goal->title}",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.goal-reminder',
            with: [
                'goal' => $this->goal,
                'reminder' => $this->reminder,
                'goalUrl' => route('goals.show', $this->goal->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
