<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Pest\Browser\Api\AwaitableWebpage;
use Relaticle\Chat\Livewire\App\Chat\ChatAllChatsPanel;

mutates(ChatAllChatsPanel::class);

/**
 * Extra Email Integration nav items push this trigger under the sticky footer.
 * Playwright's hit-tested click then times out; scroll the nav and click in JS.
 */
function openAllChatsFromSidebar(AwaitableWebpage $page): void
{
    $page->script(<<<'JS'
        (() => {
            window.Alpine?.store('sidebar')?.open();
            if (! window.Livewire?.dispatch) {
                throw new Error('Livewire is not available.');
            }
            window.Livewire.dispatch('chat:open-all-chats');
            return true;
        })();
    JS);

    $page->wait(0.5)
        ->assertVisible('[data-chat-all-chats-panel]');
}

it('opens the all-chats flyout from the sidebar trigger and lists chats', function (): void {
    $user = User::factory()->withWorkspace()->create();
    $workspace = $user->ownedWorkspaces()->first();

    $rows = [
        ['id' => 'cb1', 'participant_type' => 'user', 'participant_id' => $user->getKey(), 'workspace_id' => $user->current_workspace_id, 'title' => 'Acme onboarding', 'created_at' => now()->subMinutes(20), 'updated_at' => now()->subMinutes(20)],
        ['id' => 'cb2', 'participant_type' => 'user', 'participant_id' => $user->getKey(), 'workspace_id' => $user->current_workspace_id, 'title' => 'Q3 pipeline review', 'created_at' => now()->subMinutes(19), 'updated_at' => now()->subMinutes(19)],
    ];
    for ($i = 3; $i <= 8; $i++) {
        $rows[] = [
            'id' => "cb{$i}",
            'participant_type' => 'user',
            'participant_id' => $user->getKey(),
            'workspace_id' => $user->current_workspace_id,
            'title' => "Filler {$i}",
            'created_at' => now()->subMinutes(20 - $i),
            'updated_at' => now()->subMinutes(20 - $i),
        ];
    }
    DB::table('agent_conversations')->insert($rows);

    $page = loginViaBrowser($user)
        ->assertPathIs("/app/{$workspace->slug}")
        ->assertSourceHas('aria-label="Open all chats"');

    openAllChatsFromSidebar($page);

    $page->assertSee('Acme onboarding')
        ->assertSee('Q3 pipeline review');
});

it('navigates to a chat when clicked from the panel', function (): void {
    $user = User::factory()->withWorkspace()->create();
    $workspace = $user->ownedWorkspaces()->first();

    $rows = [[
        'id' => 'cnav1',
        'participant_type' => 'user',
        'participant_id' => $user->getKey(),
        'workspace_id' => $user->current_workspace_id,
        'title' => 'Navigate to me',
        'created_at' => now(),
        'updated_at' => now(),
    ]];
    for ($i = 2; $i <= 8; $i++) {
        $rows[] = [
            'id' => "cnav{$i}",
            'participant_type' => 'user',
            'participant_id' => $user->getKey(),
            'workspace_id' => $user->current_workspace_id,
            'title' => "Filler {$i}",
            'created_at' => now()->subMinutes($i),
            'updated_at' => now()->subMinutes($i),
        ];
    }
    DB::table('agent_conversations')->insert($rows);

    $page = loginViaBrowser($user)
        ->assertPathIs("/app/{$workspace->slug}");

    openAllChatsFromSidebar($page);

    $page->click('[data-chat-all-chats-panel] a[href*="cnav1"]')
        ->assertPathIs("/app/{$workspace->slug}/chats/cnav1");
});

it('does not restore an open flyout when the browser goes back', function (): void {
    $user = User::factory()->withWorkspace()->create();
    $workspace = $user->ownedWorkspaces()->first();

    $rows = [];
    for ($i = 1; $i <= 8; $i++) {
        $rows[] = [
            'id' => "cback{$i}",
            'participant_type' => 'user',
            'participant_id' => $user->getKey(),
            'workspace_id' => $user->current_workspace_id,
            'title' => "Filler {$i}",
            'created_at' => now()->subMinutes(20 - $i),
            'updated_at' => now()->subMinutes(20 - $i),
        ];
    }
    DB::table('agent_conversations')->insert($rows);

    $page = loginViaBrowser($user)
        ->assertPathIs("/app/{$workspace->slug}");

    openAllChatsFromSidebar($page);

    $page->script("window.Livewire.navigate('/app/{$workspace->slug}/people')");

    $page->waitForText('No people')
        ->back()
        ->waitForText('Get started')
        ->wait(0.5);

    $page->assertMissing('[data-chat-all-chats-panel]');
});
