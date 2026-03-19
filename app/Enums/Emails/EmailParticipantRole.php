<?php

declare(strict_types=1);

namespace App\Enums\Emails;

enum EmailParticipantRole: string
{
    case FROM = 'from';
    case TO = 'to';
    case CC = 'cc';
    case BCC = 'bcc';
}
