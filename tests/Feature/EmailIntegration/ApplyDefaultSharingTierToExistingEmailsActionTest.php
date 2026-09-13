<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\EmailIntegration\Actions\ApplyDefaultSharingTierToExistingEmailsAction;
use Relaticle\EmailIntegration\Enums\EmailPrivacyTier;
use Relaticle\EmailIntegration\Models\ConnectedAccount;
use Relaticle\EmailIntegration\Models\Email;

mutates(ApplyDefaultSharingTierToExistingEmailsAction::class);

beforeEach(function (): void {
    $this->owner = User::factory()->withTeam()->create();
    $this->actingAs($this->owner);
    $this->team = $this->owner->currentTeam;
    Filament::setTenant($this->team);

    $this->account = ConnectedAccount::withoutEvents(fn () => ConnectedAccount::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->owner->id,
    ]));

    $this->action = app(ApplyDefaultSharingTierToExistingEmailsAction::class);
});

function makeRetroactiveEmail(array $overrides = []): Email
{
    return Email::factory()->create(array_merge([
        'team_id' => test()->team->id,
        'user_id' => test()->owner->id,
        'connected_account_id' => test()->account->getKey(),
        'privacy_tier' => EmailPrivacyTier::METADATA_ONLY,
        'privacy_tier_customized' => false,
    ], $overrides));
}

it('updates non-customized emails for a user', function (): void {
    $email = makeRetroactiveEmail();
    $customized = makeRetroactiveEmail(['privacy_tier_customized' => true]);

    $updated = $this->action->executeForUser($this->owner, EmailPrivacyTier::FULL);

    expect($updated)->toBe(1)
        ->and($email->fresh()->privacy_tier)->toBe(EmailPrivacyTier::FULL)
        ->and($customized->fresh()->privacy_tier)->toBe(EmailPrivacyTier::METADATA_ONLY);
});

it('updates team emails only for members who follow the workspace default', function (): void {
    $member = User::factory()->create(['current_team_id' => $this->team->id, 'default_email_sharing_tier' => null]);
    $this->team->users()->attach($member, ['role' => 'editor']);

    $memberAccount = ConnectedAccount::withoutEvents(fn () => ConnectedAccount::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $member->id,
    ]));

    $ownerEmail = makeRetroactiveEmail();
    $memberEmail = Email::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $member->id,
        'connected_account_id' => $memberAccount->getKey(),
        'privacy_tier' => EmailPrivacyTier::METADATA_ONLY,
        'privacy_tier_customized' => false,
    ]);

    $overrideMember = User::factory()->create([
        'current_team_id' => $this->team->id,
        'default_email_sharing_tier' => EmailPrivacyTier::PRIVATE,
    ]);
    $this->team->users()->attach($overrideMember, ['role' => 'editor']);

    $overrideAccount = ConnectedAccount::withoutEvents(fn () => ConnectedAccount::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $overrideMember->id,
    ]));

    $overrideEmail = Email::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $overrideMember->id,
        'connected_account_id' => $overrideAccount->getKey(),
        'privacy_tier' => EmailPrivacyTier::PRIVATE,
        'privacy_tier_customized' => false,
    ]);

    $updated = $this->action->executeForTeam($this->team, EmailPrivacyTier::FULL);

    expect($updated)->toBe(2)
        ->and($ownerEmail->fresh()->privacy_tier)->toBe(EmailPrivacyTier::FULL)
        ->and($memberEmail->fresh()->privacy_tier)->toBe(EmailPrivacyTier::FULL)
        ->and($overrideEmail->fresh()->privacy_tier)->toBe(EmailPrivacyTier::PRIVATE);
});

it('updates non-customized emails across every workspace for a user', function (): void {
    $otherTeam = Team::factory()->create([
        'user_id' => $this->owner->getKey(),
        'default_email_sharing_tier' => EmailPrivacyTier::METADATA_ONLY,
    ]);
    $this->owner->teams()->attach($otherTeam, ['role' => 'admin']);

    $otherAccount = ConnectedAccount::withoutEvents(fn () => ConnectedAccount::factory()->create([
        'team_id' => $otherTeam->getKey(),
        'user_id' => $this->owner->getKey(),
    ]));

    $currentTeamEmail = makeRetroactiveEmail();
    $otherTeamEmail = Email::factory()->create([
        'team_id' => $otherTeam->getKey(),
        'user_id' => $this->owner->getKey(),
        'connected_account_id' => $otherAccount->getKey(),
        'privacy_tier' => EmailPrivacyTier::METADATA_ONLY,
        'privacy_tier_customized' => false,
    ]);

    $updated = $this->action->executeForUser($this->owner, EmailPrivacyTier::FULL);

    expect($updated)->toBe(2)
        ->and($currentTeamEmail->fresh()->privacy_tier)->toBe(EmailPrivacyTier::FULL)
        ->and($otherTeamEmail->fresh()->privacy_tier)->toBe(EmailPrivacyTier::FULL);
});

it('resolves each workspace default when resetting a user override', function (): void {
    $privateTeam = Team::factory()->create([
        'user_id' => $this->owner->getKey(),
        'default_email_sharing_tier' => EmailPrivacyTier::PRIVATE,
    ]);
    $this->owner->teams()->attach($privateTeam, ['role' => 'admin']);
    $this->team->update(['default_email_sharing_tier' => EmailPrivacyTier::FULL]);

    $privateAccount = ConnectedAccount::withoutEvents(fn () => ConnectedAccount::factory()->create([
        'team_id' => $privateTeam->getKey(),
        'user_id' => $this->owner->getKey(),
    ]));

    $fullWorkspaceEmail = makeRetroactiveEmail(['privacy_tier' => EmailPrivacyTier::SUBJECT]);
    $privateWorkspaceEmail = Email::factory()->create([
        'team_id' => $privateTeam->getKey(),
        'user_id' => $this->owner->getKey(),
        'connected_account_id' => $privateAccount->getKey(),
        'privacy_tier' => EmailPrivacyTier::SUBJECT,
        'privacy_tier_customized' => false,
    ]);

    $updated = $this->action->executeForUserUsingWorkspaceDefaults($this->owner);

    expect($updated)->toBe(2)
        ->and($fullWorkspaceEmail->fresh()->privacy_tier)->toBe(EmailPrivacyTier::FULL)
        ->and($privateWorkspaceEmail->fresh()->privacy_tier)->toBe(EmailPrivacyTier::PRIVATE);
});

it('includes team owner emails when the owner is not on the team_user pivot', function (): void {
    $this->team->users()->detach($this->owner->getKey());

    $email = makeRetroactiveEmail();

    $updated = $this->action->executeForTeam($this->team, EmailPrivacyTier::FULL);

    expect($updated)->toBe(1)
        ->and($email->fresh()->privacy_tier)->toBe(EmailPrivacyTier::FULL);
});
