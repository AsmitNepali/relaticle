<?php

namespace App\Enums\Emails;

use Filament\Support\Contracts\HasLabel;


enum EmailDirection: string implements HasLabel
{
    case INBOUND = 'inbound';
    case OUTBOUND = 'outbound';

    public function getLabel(): string
    {
        return match ($this) {
            self::INBOUND => 'Inbound',
            self::OUTBOUND => 'Outbound',
        };
    }
}
