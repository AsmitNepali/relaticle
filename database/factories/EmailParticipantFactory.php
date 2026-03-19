<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

final class EmailParticipantFactory extends Factory
{
    protected $model = EmailParticipant::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
