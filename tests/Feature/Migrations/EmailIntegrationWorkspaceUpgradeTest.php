<?php

declare(strict_types=1);

use App\Support\Migrations\TenantMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

mutates(TenantMigration::class);

function runConnectedAccountsMigration(): void
{
    $migration = require database_path('migrations/2026_03_21_050414_create_connected_accounts_table.php');
    $migration->up();
}

test('connected accounts migration creates workspace_id when teams was already renamed', function (): void {
    expect(Schema::hasTable('workspaces'))->toBeTrue()
        ->and(Schema::hasTable('teams'))->toBeFalse()
        ->and(TenantMigration::usesWorkspacesTable())->toBeTrue();

    DB::statement('DROP TABLE IF EXISTS connected_accounts CASCADE');

    runConnectedAccountsMigration();

    expect(Schema::hasColumn('connected_accounts', 'workspace_id'))->toBeTrue()
        ->and(Schema::hasColumn('connected_accounts', 'team_id'))->toBeFalse();

    Artisan::call('migrate', ['--force' => true]);
});

test('email sharing tier columns can be added to workspaces after the rename', function (): void {
    expect(Schema::hasTable('workspaces'))->toBeTrue()
        ->and(Schema::hasTable('teams'))->toBeFalse();

    if (Schema::hasColumn('workspaces', 'default_email_sharing_tier')) {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropColumn([
                'default_email_sharing_tier',
                'contact_creation_mode',
                'auto_create_companies',
            ]);
        });
    }

    $migration = require database_path('migrations/2026_03_21_055334_add_default_email_sharing_tier_to_teams.php');
    $migration->up();

    expect(Schema::hasColumn('workspaces', 'default_email_sharing_tier'))->toBeTrue()
        ->and(Schema::hasColumn('workspaces', 'contact_creation_mode'))->toBeTrue()
        ->and(Schema::hasColumn('workspaces', 'auto_create_companies'))->toBeTrue();
});
