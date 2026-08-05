<?php

namespace App\TrackedJob\Application\Input;

use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;

/**
 * Application input object carrying normalized tracked job values for create/update use cases.
 */
final readonly class TrackedJobInput
{
    public function __construct(
        public ?string $company = null,
        public ?string $title = null,
        public ?ContractType $contractType = null,
        public ?string $location = null,
        public ?RemoteMode $remoteMode = null,
        public ?string $remuneration = null,
        public ?string $offerUrl = null,
        public ?string $notes = null,
        public ?\DateTimeImmutable $applicationDate = null,
        public ?\DateTimeImmutable $effectiveFollowUpDate = null,
        public ?\DateTimeImmutable $firstContactDate = null,
        public ?\DateTimeImmutable $preliminaryInterviewDate = null,
        public ?\DateTimeImmutable $secondInterviewDate = null,
        public ?string $hrContactName = null,
        public ?string $businessContactName = null,
        public ?int $subjectiveRelevance = null,
        public ?TrackedJobStatus $status = null,
    ) {
    }
}