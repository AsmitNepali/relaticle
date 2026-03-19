<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

final class EmailAccountFactory extends Factory
{
    protected $model = EmailAccount::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
