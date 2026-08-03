<?php

namespace App\TrackedJob\Application;

/**
 * Application input object carrying filters accepted by the tracked job CSV export use case.
 */
final readonly class ExportTrackedJobsInput
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(private array $filters)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toFilters(): array
    {
        return $this->filters;
    }
}
