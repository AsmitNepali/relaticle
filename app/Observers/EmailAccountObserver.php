<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\Email\InitialEmailSyncJob;
use App\Models\EmailAccount;

final class EmailAccountObserver
{
    public function creating(EmailAccount $emailAccount): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            $emailAccount->user_id = $user->getKey();
            $emailAccount->team_id = $user->currentTeam->getKey();
        }
    }

    public function created(EmailAccount $emailAccount): void
    {
        // Dispatch initial historical backfill after account is connected
        InitialEmailSyncJob::dispatch($emailAccount)
            ->afterCommit();
    }

    public function deleted(EmailAccount $emailAccount): void
    {
        // Cancel any pending sync jobs for this account
        // In production, remove from queue via Horizon tags
    }
}
