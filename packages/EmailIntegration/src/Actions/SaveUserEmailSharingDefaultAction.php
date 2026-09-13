<?php

declare(strict_types=1);

namespace Relaticle\EmailIntegration\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Relaticle\EmailIntegration\Enums\EmailPrivacyTier;
use Relaticle\EmailIntegration\Services\PrivacyService;

final readonly class SaveUserEmailSharingDefaultAction
{
    public function __construct(
        private UpdateUserEmailPrivacySettingsAction $updateSettings,
        private ApplyDefaultSharingTierToExistingEmailsAction $applyRetroactive,
        private PrivacyService $privacy,
    ) {}

    public function execute(User $user, ?EmailPrivacyTier $storedTier, EmailPrivacyTier $effectiveTier): void
    {
        $previousEffectiveTier = $this->privacy->effectiveSharingTierForUser($user);

        DB::transaction(function () use ($user, $storedTier, $effectiveTier, $previousEffectiveTier): void {
            $this->updateSettings->execute($user, $storedTier);

            if ($previousEffectiveTier !== $effectiveTier) {
                $this->applyRetroactive->executeForUser($user, $effectiveTier);
            }
        });
    }
}
