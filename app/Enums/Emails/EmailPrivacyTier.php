<?php

declare(strict_types=1);

namespace App\Enums\Emails;

use Filament\Support\Contracts\HasLabel;

enum EmailPrivacyTier: string implements HasLabel
{
    case METADATA_ONLY = 'metadata_only';
    case SUBJECT_ONLY = 'subject_only';
    case FULL_ACCESS = 'full_access';

    public function getLabel(): string
    {
        return match ($this) {
            self::METADATA_ONLY => 'Metadata only (participants + timestamps)',
            self::SUBJECT_ONLY => 'Subject line + metadata',
            self::FULL_ACCESS => 'Full access (body, attachments)',
        };
    }
}
