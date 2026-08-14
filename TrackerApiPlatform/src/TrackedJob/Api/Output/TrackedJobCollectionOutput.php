<?php

declare(strict_types=1);

/*
 * API output DTO for tracked job collections.
 * It preserves the legacy pagination envelope used by the frontend.
 */

namespace App\TrackedJob\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class TrackedJobCollectionOutput
{
    /** @param list<TrackedJobOutput> $items */
    public function __construct(
        #[Groups(['tracked_job:list'])]
        public array $items,
        #[Groups(['tracked_job:list'])]
        public int $page,
        #[Groups(['tracked_job:list'])]
        public int $pageSize,
        #[Groups(['tracked_job:list'])]
        public bool $hasMore,
    ) {
    }
}
