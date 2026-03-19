<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('team_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('email_account_id')->constrained()->cascadeOnDelete();

            // Provider identifiers
            $table->string('message_id');                    // Provider's message ID (dedup key)
            $table->string('thread_id')->nullable();         // Provider's thread ID

            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();       // Plain-text body
            $table->longText('body_html')->nullable();       // HTML body
            $table->timestamp('sent_at');

            $table->string('direction', 20);                 // inbound | outbound

            // Privacy
            $table->string('privacy_tier', 30)->default('metadata_only'); // metadata_only | subject_only | full_access
            $table->boolean('shared_with_team')->default(false);

            $table->string('creation_source', 50)->default('system');

            $table->timestamps();
            $table->softDeletes();

            // Dedup: one message_id per email account
            $table->unique(['email_account_id', 'message_id']);

            // Query patterns
            $table->index(['team_id', 'thread_id']);
            $table->index(['team_id', 'sent_at']);
            $table->index(['team_id', 'deleted_at', 'creation_source', 'created_at'], 'idx_email_addresses_team_activity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
