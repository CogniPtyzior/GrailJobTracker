<?php

namespace App\TrackedJob\Application\Input;

use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\ContactName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\OfferUrl;
use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;

/**
 * Application input object carrying normalized tracked job values for create/update use cases.
 */
final readonly class TrackedJobInput
{
    public function __construct(
        public ?CompanyName $company = null,
        public ?JobTitle $title = null,
        public ?ContractType $contractType = null,
        public ?string $location = null,
        public ?RemoteMode $remoteMode = null,
        public ?string $remuneration = null,
        public ?OfferUrl $offerUrl = null,
        public ?TrackedJobNotes $notes = null,
        public ?\DateTimeImmutable $applicationDate = null,
        public ?\DateTimeImmutable $effectiveFollowUpDate = null,
        public ?\DateTimeImmutable $firstContactDate = null,
        public ?\DateTimeImmutable $preliminaryInterviewDate = null,
        public ?\DateTimeImmutable $secondInterviewDate = null,
        public ?ContactName $hrContactName = null,
        public ?ContactName $businessContactName = null,
        public ?int $subjectiveRelevance = null,
        public ?TrackedJobStatus $status = null,
    ) {
    }
}
