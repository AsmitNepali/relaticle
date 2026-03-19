<?php

declare(strict_types=1);

namespace App\Models\Concerns\Emails;

use App\Models\Email;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasEmails
{
    public function emails(): MorphToMany
    {
        return $this->morphToMany(Email::class, 'emailable')
            ->withTimestamps()
            ->orderByDesc('sent_at');
    }
}
