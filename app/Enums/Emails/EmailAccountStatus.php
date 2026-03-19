<?php

declare(strict_types=1);

namespace App\Enums\Emails;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmailAccountStatus: string implements HasColor, HasLabel
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case ERROR = 'error';
    case DISCONNECTED = 'disconnected';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Token Expired',
            self::ERROR => 'Sync Error',
            self::DISCONNECTED => 'Disconnected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::EXPIRED => 'warning',
            self::ERROR => 'danger',
            self::DISCONNECTED => 'gray',
        };
    }
}
