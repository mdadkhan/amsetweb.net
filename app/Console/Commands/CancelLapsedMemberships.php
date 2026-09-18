<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class CancelLapsedMemberships extends Command
{
    protected $signature = 'amset:cancel-lapsed-memberships {--grace-days=0 : Days past expiration to allow before cancelling}';

    protected $description = 'Cancel active memberships that were not renewed (via Stripe/PayPal) after they expired.';

    public function handle(): int
    {
        $graceDays = (int) $this->option('grace-days');

        $members = Member::query()->lapsed($graceDays)->get();

        foreach ($members as $member) {
            $member->update(['status' => 'cancelled']);
        }

        $this->info("Cancelled {$members->count()} lapsed membership(s).");

        return self::SUCCESS;
    }
}
