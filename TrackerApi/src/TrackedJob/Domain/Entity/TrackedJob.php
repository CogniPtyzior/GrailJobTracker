<?php

namespace App\TrackedJob\Domain\Entity;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\ContactName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\OfferUrl;
use App\TrackedJob\Domain\ValueObject\SubjectiveRelevance;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;

/**
 * Domain entity that owns tracked-job state transitions and normalization rules.
 */
final class TrackedJob
{
    private TrackedJobId $id;
    private User $owner;
    private ?CompanyName $company = null;
    private ?JobTitle $title = null;
    private ?ContractType $contractType = ContractType::CDI;
    private ?string $location = null;
    private ?RemoteMode $remoteMode = null;
    private ?string $remuneration = null;
    private ?OfferUrl $offerUrl = null;
    private ?TrackedJobNotes $notes = null;
    private TrackedJobTimeline $timeline;
    private ?ContactName $hrContactName = null;
    private ?ContactName $businessContactName = null;
    private ?SubjectiveRelevance $subjectiveRelevance = null;
    private TrackedJobStatus $status = TrackedJobStatus::DRAFT;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $owner)
    {
        $this->id = TrackedJobId::new();
        $this->owner = $owner;
        $this->timeline = TrackedJobTimeline::empty();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function openFor(User $owner): self
    {
        return new self($owner);
    }

    public static function reconstitute(
        TrackedJobId $id,
        User $owner,
        ?CompanyName $company,
        ?JobTitle $title,
        ?ContractType $contractType,
        ?string $location,
        ?RemoteMode $remoteMode,
        ?string $remuneration,
        ?OfferUrl $offerUrl,
        ?TrackedJobNotes $notes,
        ?\DateTimeImmutable $applicationDate,
        ?\DateTimeImmutable $plannedFollowUpDate,
        ?\DateTimeImmutable $effectiveFollowUpDate,
        ?\DateTimeImmutable $firstContactDate,
        ?\DateTimeImmutable $preliminaryInterviewDate,
        ?\DateTimeImmutable $secondInterviewDate,
        ?ContactName $hrContactName,
        ?ContactName $businessContactName,
        ?SubjectiveRelevance $subjectiveRelevance,
        TrackedJobStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        $trackedJob = new self($owner);
        $trackedJob->id = $id;
        $trackedJob->company = $company;
        $trackedJob->title = $title;
        $trackedJob->contractType = $contractType ?? ContractType::CDI;
        $trackedJob->location = self::trimOrNull($location);
        $trackedJob->remoteMode = $remoteMode;
        $trackedJob->remuneration = self::trimOrNull($remuneration);
        $trackedJob->offerUrl = $offerUrl;
        $trackedJob->notes = $notes;
        $trackedJob->timeline = TrackedJobTimeline::fromPersistedState(
            $applicationDate,
            $plannedFollowUpDate,
            $effectiveFollowUpDate,
            $firstContactDate,
            $preliminaryInterviewDate,
            $secondInterviewDate,
        );
        $trackedJob->hrContactName = $hrContactName;
        $trackedJob->businessContactName = $businessContactName;
        $trackedJob->subjectiveRelevance = $subjectiveRelevance;
        $trackedJob->status = $status;
        $trackedJob->createdAt = $createdAt;
        $trackedJob->updatedAt = $updatedAt;

        return $trackedJob;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function updatePosition(
        ?CompanyName $company,
        ?JobTitle $title,
        ?ContractType $contractType,
        ?string $location,
        ?RemoteMode $remoteMode,
        ?string $remuneration,
        ?OfferUrl $offerUrl,
        ?TrackedJobNotes $notes,
    ): void {
        $this->company = $company;
        $this->title = $title;
        $this->contractType = $contractType ?? ContractType::CDI;
        $this->location = self::trimOrNull($location);
        $this->remoteMode = $remoteMode;
        $this->remuneration = self::trimOrNull($remuneration);
        $this->offerUrl = $offerUrl;
        $this->notes = $notes;
        $this->touch();
    }

    public function updateTimeline(TrackedJobTimeline $timeline): void
    {
        $this->timeline = $timeline;

        if (!$this->status->isFinal()) {
            $this->status = $this->timeline->inferStatus();
        }

        $this->touch();
    }

    public function updateContacts(?ContactName $hrContactName, ?ContactName $businessContactName): void
    {
        $this->hrContactName = $hrContactName;
        $this->businessContactName = $businessContactName;
        $this->touch();
    }

    public function updateRelevance(?SubjectiveRelevance $subjectiveRelevance): void
    {
        $this->subjectiveRelevance = $subjectiveRelevance;
        $this->touch();
    }

    public function requestStatus(?TrackedJobStatus $requestedStatus = null): void
    {
        if ($requestedStatus?->isFinal()) {
            $this->status = $requestedStatus;
            $this->touch();

            return;
        }

        if ($this->status->isFinal() && $requestedStatus === null) {
            return;
        }

        $this->status = $this->timeline->inferStatus();
        $this->touch();
    }

    public function getId(): TrackedJobId
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function company(): ?CompanyName
    {
        return $this->company;
    }

    public function title(): ?JobTitle
    {
        return $this->title;
    }

    public function getContractType(): ?ContractType
    {
        return $this->contractType;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getRemoteMode(): ?RemoteMode
    {
        return $this->remoteMode;
    }

    public function getRemuneration(): ?string
    {
        return $this->remuneration;
    }

    public function offerUrl(): ?OfferUrl
    {
        return $this->offerUrl;
    }

    public function notes(): ?TrackedJobNotes
    {
        return $this->notes;
    }

    public function timeline(): TrackedJobTimeline
    {
        return $this->timeline;
    }

    public function hrContactName(): ?ContactName
    {
        return $this->hrContactName;
    }

    public function businessContactName(): ?ContactName
    {
        return $this->businessContactName;
    }

    public function subjectiveRelevance(): ?SubjectiveRelevance
    {
        return $this->subjectiveRelevance;
    }

    public function getSubjectiveRelevance(): ?int
    {
        return $this->subjectiveRelevance?->value();
    }

    public function getStatus(): TrackedJobStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function trimOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
