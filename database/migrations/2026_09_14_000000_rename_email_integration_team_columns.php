<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connected_accounts', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('email_batches', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('emails', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('email_threads', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('email_templates', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('email_signatures', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('email_shares', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('public_email_domains', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('protected_recipients', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('email_blocklists', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('team_email_blocklists', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        Schema::table('ai_summaries', function (Blueprint $table): void {
            $table->renameColumn('team_id', 'workspace_id');
        });

        $this->renameCatalogObjects();

        Schema::rename('team_email_blocklists', 'workspace_email_blocklists');
    }

    /**
     * Index, constraint and sequence names are read back from the catalog rather
     * than listed: production lost several named uniques and its pre-2026 foreign
     * keys, so a fixed list would fail there on the first missing object.
     * Constraints go first, because renaming a unique or primary key carries its
     * index along and the index sweep should only see what is left.
     */
    private function renameCatalogObjects(): void
    {
        DB::statement(<<<'SQL'
            DO $$
            DECLARE r record;
            BEGIN
                FOR r IN
                    SELECT c.relname AS table_name, con.conname
                    FROM pg_constraint con
                    JOIN pg_class c ON c.oid = con.conrelid
                    JOIN pg_namespace n ON n.oid = c.relnamespace
                    WHERE n.nspname = 'public' AND con.conname LIKE '%team%'
                LOOP
                    EXECUTE format(
                        'ALTER TABLE %I RENAME CONSTRAINT %I TO %I',
                        r.table_name, r.conname, replace(r.conname, 'team', 'workspace')
                    );
                END LOOP;

                FOR r IN
                    SELECT indexname FROM pg_indexes
                    WHERE schemaname = 'public' AND indexname LIKE '%team%'
                LOOP
                    EXECUTE format(
                        'ALTER INDEX public.%I RENAME TO %I',
                        r.indexname, replace(r.indexname, 'team', 'workspace')
                    );
                END LOOP;

                FOR r IN
                    SELECT sequence_name FROM information_schema.sequences
                    WHERE sequence_schema = 'public' AND sequence_name LIKE '%team%'
                LOOP
                    EXECUTE format(
                        'ALTER SEQUENCE public.%I RENAME TO %I',
                        r.sequence_name, replace(r.sequence_name, 'team', 'workspace')
                    );
                END LOOP;
            END $$;
        SQL);
    }
};
