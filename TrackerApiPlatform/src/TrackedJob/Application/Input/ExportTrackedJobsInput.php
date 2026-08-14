<?php

declare(strict_types=1);

/*
 * Application input for tracked job CSV export filters.
 * It keeps transport payloads out of the use case while preserving frontend-compatible filter names.
 */

namespace App\TrackedJob\Application\Input;

final readonly class ExportTrackedJobsInput
{
    /** @param array<string, mixed> $filters */
    public function __construct(private array $filters)
    {
    }

    /** @return array<string, mixed> */
    public function toFilters(): array
    {
        return $this->filters;
    }
}
