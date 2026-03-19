<?php

declare(strict_types=1);

namespace App\Enums\Emails;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EmailProvider: string implements HasColor, HasIcon, HasLabel
{
    case GMAIL = 'gmail';
    case MICROSOFT = 'microsoft';

    public function getLabel(): string
    {
        return match ($this) {
            self::GMAIL => 'Gmail',
            self::MICROSOFT => 'Outlook / Microsoft 365',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::GMAIL => 'danger',
            self::MICROSOFT => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::GMAIL => 'heroicon-o-envelope',
            self::MICROSOFT => 'heroicon-o-envelope',
        };
    }
}
