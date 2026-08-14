<?php

declare(strict_types=1);

/*
 * Application use case for CSV export.
 * It reuses the same owner-scoped search port as list reads and caps the export size to protect the API.
 */

namespace App\TrackedJob\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Application\Export\TrackedJobCsvExporter;
use App\TrackedJob\Application\Input\ExportTrackedJobsInput;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

final readonly class ExportTrackedJobsCsv
{
    private const EXPORT_LIMIT = 5000;

    public function __construct(
        private TrackedJobRepositoryInterface $trackedJobRepository,
        private TrackedJobCsvExporter $csvExporter,
    ) {
    }

    public function handle(User $owner, ExportTrackedJobsInput $input): string
    {
        $result = $this->trackedJobRepository->search($owner->getId(), $input->toFilters(), 1, self::EXPORT_LIMIT);

        return $this->csvExporter->export($result['items']);
    }
}
