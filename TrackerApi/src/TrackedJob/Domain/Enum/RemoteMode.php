<?php

namespace App\TrackedJob\Domain\Enum;

enum RemoteMode: string
{
    case NON = 'NON';
    case HYBRID = 'HYBRID';
    case FLEXIBLE_HYBRID = 'FLEXIBLE_HYBRID';
    case FULL = 'FULL';
}
