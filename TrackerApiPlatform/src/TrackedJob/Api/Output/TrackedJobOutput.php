<?php

declare(strict_types=1);

/*
 * API output DTO for tracked job fields.
 * It preserves the frontend field names through a shared read group used by list and item operations.
 * Operation-specific envelope groups are kept on the collection and item wrapper DTOs.
 */

namespace App\TrackedJob\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class TrackedJobOutput
{
    public function __construct(
        #[Groups(['tracked_job:read'])]
        public string $id,
        #[Groups(['tracked_job:read'])]
        public ?string $company,
        #[Groups(['tracked_job:read'])]
        public ?string $title,
        #[Groups(['tracked_job:read'])]
        public ?string $contractType,
        #[Groups(['tracked_job:read'])]
        public ?string $location,
        #[Groups(['tracked_job:read'])]
        public ?string $remoteMode,
        #[Groups(['tracked_job:read'])]
        public ?string $remuneration,
        #[Groups(['tracked_job:read'])]
        public ?string $offerUrl,
        #[Groups(['tracked_job:read'])]
        public ?string $notes,
        #[Groups(['tracked_job:read'])]
        public ?string $applicationDate,
        #[Groups(['tracked_job:read'])]
        public ?string $plannedFollowUpDate,
        #[Groups(['tracked_job:read'])]
        public ?string $effectiveFollowUpDate,
        #[Groups(['tracked_job:read'])]
        public ?string $firstContactDate,
        #[Groups(['tracked_job:read'])]
        public ?string $preliminaryInterviewDate,
        #[Groups(['tracked_job:read'])]
        public ?string $secondInterviewDate,
        #[Groups(['tracked_job:read'])]
        public ?string $hrContactName,
        #[Groups(['tracked_job:read'])]
        public ?string $businessContactName,
        #[Groups(['tracked_job:read'])]
        public ?int $subjectiveRelevance,
        #[Groups(['tracked_job:read'])]
        public string $status,
        #[Groups(['tracked_job:read'])]
        public string $createdAt,
        #[Groups(['tracked_job:read'])]
        public string $updatedAt,
    ) {
    }
}
