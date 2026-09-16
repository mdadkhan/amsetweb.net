<?php

namespace App\Console\Commands;

use App\Mail\MembershipRenewalReminder;
use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMembershipRenewalReminders extends Command
{
    protected $signature = 'amset:send-renewal-reminders {--days=30 : Days before expiration to notify}';

    protected $description = 'Email active members whose membership is about to expire.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $members = Member::query()
            ->expiringWithin($days)
            ->where('renewal_reminder_sent', false)
            ->get();

        foreach ($members as $member) {
            Mail::to($member->email)->queue(new MembershipRenewalReminder($member));
            $member->update(['renewal_reminder_sent' => true]);
        }

        $this->info("Sent {$members->count()} membership renewal reminder(s).");

        return self::SUCCESS;
    }
}
