<?php

declare(strict_types=1);

/*
 * Application result returned by tracked job search.
 * It carries domain aggregates and pagination metadata without leaking persistence records.
 */

namespace App\TrackedJob\Application\Result;

use App\TrackedJob\Domain\Entity\TrackedJob;

final readonly class SearchTrackedJobsResult
{
    /** @param list<TrackedJob> $items */
    public function __construct(
        public array $items,
        public bool $hasMore,
    ) {
    }
}
