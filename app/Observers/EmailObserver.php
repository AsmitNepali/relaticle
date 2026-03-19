<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Email;

final class EmailObserver
{
    public function __construct(
        private EmailLinkingService $linkingService,
    ) {}

    public function created(Email $email): void
    {
        // Auto-link email to People / Company records based on participants
        $this->linkingService->linkEmail($email);
    }
}
