<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_participants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('email_id')->constrained('emails')->cascadeOnDelete();

            $table->string('email_address');
            $table->string('name')->nullable();
            $table->string('role', 10);                      // from | to | cc | bcc

            // Resolved FK — nullable, filled by auto-linking logic
            $table->foreignUlid('person_id')
                ->nullable()
                ->constrained('people')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['email_id', 'role']);
            $table->index('email_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_participants');
    }
};
