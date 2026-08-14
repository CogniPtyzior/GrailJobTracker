<?php

declare(strict_types=1);

/*
 * Remote work values supported by tracked job offers.
 * They preserve the existing frontend contract while keeping the vocabulary in the domain model.
 */

namespace App\TrackedJob\Domain\Enum;

enum RemoteMode: string
{
    case NON = 'NON';
    case HYBRID = 'HYBRID';
    case FLEXIBLE_HYBRID = 'FLEXIBLE_HYBRID';
    case FULL = 'FULL';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $item): string => $item->value, self::cases());
    }
}
