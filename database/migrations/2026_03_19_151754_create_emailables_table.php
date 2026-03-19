<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emailables', function (Blueprint $table): void {
            $table->foreignUlid('email_id')->constrained('emails')->cascadeOnDelete();
            $table->ulidMorphs('emailable');                 // emailable_type, emailable_id (ULID)

            $table->primary(['email_id', 'emailable_type', 'emailable_id']);
            $table->index(['emailable_type', 'emailable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emailables');
    }
};
