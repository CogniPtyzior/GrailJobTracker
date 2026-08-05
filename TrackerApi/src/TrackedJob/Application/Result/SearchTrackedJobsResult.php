<?php

namespace App\TrackedJob\Application\Result;

use App\TrackedJob\Domain\Entity\TrackedJob;

/**
 * Application result returned by the tracked job search use case.
 */
final readonly class SearchTrackedJobsResult
{
    /**
     * @param list<TrackedJob> $items
     */
    public function __construct(
        public array $items,
        public bool $hasMore,
    ) {
    }
}
