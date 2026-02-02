<?php

namespace App\Jobs;

use App\Mail\GoalReminderMail;
use App\Models\Reminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendGoalReminders implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $reminders = Reminder::with(['goal.user', 'goal.category'])
            ->due()
            ->get();

        foreach ($reminders as $reminder) {
            $goal = $reminder->goal;
            $user = $goal->user;

            if (!$user || !$user->email) {
                continue;
            }

            // Skip if goal is completed or cancelled
            if (in_array($goal->status, ['completed', 'cancelled'])) {
                $reminder->update(['is_active' => false]);
                continue;
            }

            try {
                Mail::to($user->email)->send(new GoalReminderMail($goal, $reminder));
                $reminder->markAsSent();
            } catch (\Exception $e) {
                \Log::error("Failed to send reminder {$reminder->id}: " . $e->getMessage());
            }
        }
    }
}
