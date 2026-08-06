<?php

namespace App\TrackedJob\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Application\Export\TrackedJobCsvExporter;
use App\TrackedJob\Application\Input\ExportTrackedJobsInput;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

/**
 * Application use case that exports tracked jobs matching the active filters.
 */
final class ExportTrackedJobsCsv
{
    private const EXPORT_LIMIT = 5000;

    public function __construct(
        private readonly TrackedJobRepositoryInterface $trackedJobRepository,
        private readonly TrackedJobCsvExporter $csvExporter,
    ) {
    }

    public function handle(User $owner, ExportTrackedJobsInput $filters): string
    {
        $result = $this->trackedJobRepository->search($owner->getId(), $filters->toFilters(), 1, self::EXPORT_LIMIT);

        return $this->csvExporter->export($result['items']);
    }
}
