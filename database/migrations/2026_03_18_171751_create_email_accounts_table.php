<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->teams(); // team_id
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();

            $table->string('provider', 50);                  // gmail | microsoft
            $table->string('email_address');
            $table->string('display_name')->nullable();

            $table->text('access_token');                    // encrypted at cast level
            $table->text('refresh_token')->nullable();       // encrypted at cast level
            $table->timestamp('token_expires_at')->nullable();

            $table->string('sync_cursor')->nullable();       // Gmail historyId / MS Graph deltaToken
            $table->timestamp('last_synced_at')->nullable();
            $table->string('status', 50)->default('active'); // active | expired | error | disconnected
            $table->text('last_error')->nullable();

            $table->boolean('sync_inbox')->default(true);
            $table->boolean('sync_sent')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'provider', 'email_address']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
