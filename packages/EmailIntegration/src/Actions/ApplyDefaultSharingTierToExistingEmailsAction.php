<?php

declare(strict_types=1);

namespace Relaticle\EmailIntegration\Actions;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Relaticle\EmailIntegration\Enums\EmailPrivacyTier;
use Relaticle\EmailIntegration\Models\Email;

final readonly class ApplyDefaultSharingTierToExistingEmailsAction
{
    public function executeForTeam(Team $team, EmailPrivacyTier $tier): int
    {
        $userIds = User::query()
            ->whereNull('default_email_sharing_tier')
            ->where(function (Builder $query) use ($team): void {
                $query->whereHas('teams', fn (Builder $teamQuery) => $teamQuery->whereKey($team->getKey()))
                    ->orWhereKey($team->user_id);
            })
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return 0;
        }

        return Email::query()
            ->where('team_id', $team->getKey())
            ->whereIn('user_id', $userIds)
            ->where('privacy_tier_customized', false)
            ->update(['privacy_tier' => $tier->value]);
    }

    public function executeForUser(User $user, EmailPrivacyTier $tier): int
    {
        // ActiveAccountScope hides disconnected mailboxes from normal reads; the same
        // filter applies here so retroactive updates never touch orphaned rows.
        return Email::query()
            ->where('user_id', $user->getKey())
            ->where('privacy_tier_customized', false)
            ->update(['privacy_tier' => $tier->value]);
    }
}
