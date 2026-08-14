<?php

declare(strict_types=1);

/*
 * API Platform output DTO for reference data.
 * Serializer groups document the read contract while preserving the existing simple JSON shape.
 */

namespace App\ReferenceData\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class ReferenceDataOutput
{
    /**
     * @param list<string> $contractTypes
     * @param list<string> $remoteModes
     * @param list<string> $trackedJobStatuses
     */
    public function __construct(
        #[Groups(['reference_data:read'])]
        public array $contractTypes,
        #[Groups(['reference_data:read'])]
        public array $remoteModes,
        #[Groups(['reference_data:read'])]
        public array $trackedJobStatuses,
        #[Groups(['reference_data:read'])]
        public string $defaultContractType,
    ) {
    }
}
