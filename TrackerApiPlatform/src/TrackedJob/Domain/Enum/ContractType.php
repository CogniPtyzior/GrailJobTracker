<?php

declare(strict_types=1);

/*
 * Contract type values supported by tracked job offers.
 * They are exposed as reference data and reused by the tracked job domain during later migration steps.
 */

namespace App\TrackedJob\Domain\Enum;

enum ContractType: string
{
    case CDI = 'CDI';
    case CDD = 'CDD';
    case FREELANCE = 'FREELANCE';
    case INTERNSHIP = 'INTERNSHIP';
    case APPRENTICESHIP = 'APPRENTICESHIP';
    case OTHER = 'OTHER';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $item): string => $item->value, self::cases());
    }
}
