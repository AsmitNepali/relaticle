<?php

declare(strict_types=1);

use App\Livewire\App\Email\UserEmailPrivacySettings;
use App\Models\User;
use Filament\Facades\Filament;
use Relaticle\EmailIntegration\Actions\ApplyDefaultSharingTierToExistingEmailsAction;
use Relaticle\EmailIntegration\Actions\SaveUserEmailSharingDefaultAction;
use Relaticle\EmailIntegration\Enums\EmailPrivacyTier;
use Relaticle\EmailIntegration\Filament\Pages\UserEmailPrivacyPage;
use Relaticle\EmailIntegration\Models\ConnectedAccount;
use Relaticle\EmailIntegration\Models\Email;

mutates(
    UserEmailPrivacyPage::class,
    UserEmailPrivacySettings::class,
    ApplyDefaultSharingTierToExistingEmailsAction::class,
    SaveUserEmailSharingDefaultAction::class,
);

beforeEach(function (): void {
    $this->owner = User::factory()->withTeam()->create();
    $this->team = $this->owner->currentTeam;
});

it('grants any team member access to the my-privacy page regardless of role', function (): void {
    $member = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->team->users()->attach($member, ['role' => 'editor']);
    $this->actingAs($member);
    Filament::setTenant($this->team);

    expect(UserEmailPrivacyPage::canAccess())->toBeTrue();
});

it('persists the user default sharing tier when a member saves their preference', function (): void {
    $member = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->team->users()->attach($member, ['role' => 'editor']);
    $this->actingAs($member);
    Filament::setTenant($this->team);

    livewire(UserEmailPrivacySettings::class)
        ->set('data.default_email_sharing_tier', EmailPrivacyTier::FULL->value)
        ->callAction('saveTier', data: [
            'full_access_confirmation' => 'I understand',
        ])
        ->assertNotified('Email privacy settings saved.');

    expect($member->fresh()->default_email_sharing_tier)->toBe(EmailPrivacyTier::FULL);
});

it('retroactively updates non-customized emails when the user sharing preference changes', function (): void {
    $member = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->team->users()->attach($member, ['role' => 'editor']);
    $this->actingAs($member);
    Filament::setTenant($this->team);

    $account = ConnectedAccount::withoutEvents(fn () => ConnectedAccount::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $member->id,
    ]));

    $email = Email::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $member->id,
        'connected_account_id' => $account->getKey(),
        'privacy_tier' => EmailPrivacyTier::METADATA_ONLY,
        'privacy_tier_customized' => false,
    ]);

    $customized = Email::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $member->id,
        'connected_account_id' => $account->getKey(),
        'privacy_tier' => EmailPrivacyTier::PRIVATE,
        'privacy_tier_customized' => true,
    ]);

    livewire(UserEmailPrivacySettings::class)
        ->set('data.default_email_sharing_tier', EmailPrivacyTier::FULL->value)
        ->callAction('saveTier', data: [
            'full_access_confirmation' => 'I understand',
        ]);

    expect($email->fresh()->privacy_tier)->toBe(EmailPrivacyTier::FULL)
        ->and($customized->fresh()->privacy_tier)->toBe(EmailPrivacyTier::PRIVATE);
});

it('clears a user sharing override when selecting use workspace default', function (): void {
    $this->team->update(['default_email_sharing_tier' => EmailPrivacyTier::METADATA_ONLY]);
    $this->owner->update(['default_email_sharing_tier' => EmailPrivacyTier::SUBJECT]);
    $this->actingAs($this->owner);
    Filament::setTenant($this->team);

    $account = ConnectedAccount::withoutEvents(fn () => ConnectedAccount::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->owner->id,
    ]));

    $email = Email::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->owner->id,
        'connected_account_id' => $account->getKey(),
        'privacy_tier' => EmailPrivacyTier::SUBJECT,
        'privacy_tier_customized' => false,
    ]);

    livewire(UserEmailPrivacySettings::class)
        ->set('data.default_email_sharing_tier', '')
        ->callAction('saveTier')
        ->assertNotified('Email privacy settings saved.');

    expect($this->owner->fresh()->default_email_sharing_tier)->toBeNull()
        ->and($email->fresh()->privacy_tier)->toBe(EmailPrivacyTier::METADATA_ONLY);
});

it('requires confirmation when changing the user sharing tier to private', function (): void {
    $this->actingAs($this->owner);
    Filament::setTenant($this->team);

    livewire(UserEmailPrivacySettings::class)
        ->set('data.default_email_sharing_tier', EmailPrivacyTier::PRIVATE->value)
        ->mountAction('saveTier')
        ->assertActionMounted('saveTier');
});

it('rejects an incorrect full access confirmation phrase on my email privacy', function (): void {
    $this->actingAs($this->owner);
    Filament::setTenant($this->team);

    livewire(UserEmailPrivacySettings::class)
        ->set('data.default_email_sharing_tier', EmailPrivacyTier::FULL->value)
        ->callAction('saveTier', data: [
            'full_access_confirmation' => 'not the phrase',
        ])
        ->assertHasActionErrors(['full_access_confirmation']);

    expect($this->owner->fresh()->default_email_sharing_tier)->toBeNull();
});

it('saves without confirmation when the user sharing tier is unchanged', function (): void {
    $this->owner->update(['default_email_sharing_tier' => EmailPrivacyTier::SUBJECT]);
    $this->actingAs($this->owner);
    Filament::setTenant($this->team);

    livewire(UserEmailPrivacySettings::class)
        ->set('data.default_email_sharing_tier', EmailPrivacyTier::SUBJECT->value)
        ->callAction('saveTier')
        ->assertNotified('Email privacy settings saved.');
});

it('renders the sharing cards without blocklist controls', function (): void {
    $this->actingAs($this->owner);
    Filament::setTenant($this->team);

    livewire(UserEmailPrivacySettings::class)
        ->assertSee('Use workspace default')
        ->assertDontSee('Blocked addresses')
        ->assertDontSee('Blocked domains');
});
