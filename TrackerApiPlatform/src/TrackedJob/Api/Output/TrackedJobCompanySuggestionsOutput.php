<?php

declare(strict_types=1);

/*
 * API Platform output DTO for tracked job company suggestions.
 * It keeps the autocomplete response contract isolated from collection and item read projections.
 */

namespace App\TrackedJob\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class TrackedJobCompanySuggestionsOutput
{
    /** @param list<string> $items */
    public function __construct(
        #[Groups(['tracked_job:suggestions'])]
        public array $items,
    ) {
    }
}
