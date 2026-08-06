<?php

namespace App\TrackedJob\Domain\Entity;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

/**
 * Domain entity that owns tracked-job state transitions and normalization rules.
 */
final class TrackedJob
{
    private Uuid $id;
    private User $owner;
    private ?string $company = null;
    private ?string $title = null;
    private ?ContractType $contractType = ContractType::CDI;
    private ?string $location = null;
    private ?RemoteMode $remoteMode = null;
    private ?string $remuneration = null;
    private ?string $offerUrl = null;
    private ?string $notes = null;
    private ?\DateTimeImmutable $applicationDate = null;
    private ?\DateTimeImmutable $plannedFollowUpDate = null;
    private ?\DateTimeImmutable $effectiveFollowUpDate = null;
    private ?\DateTimeImmutable $firstContactDate = null;
    private ?\DateTimeImmutable $preliminaryInterviewDate = null;
    private ?\DateTimeImmutable $secondInterviewDate = null;
    private ?string $hrContactName = null;
    private ?string $businessContactName = null;
    private ?int $subjectiveRelevance = null;
    private TrackedJobStatus $status = TrackedJobStatus::DRAFT;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $owner)
    {
        $this->id = new UuidV7();
        $this->owner = $owner;
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public static function reconstitute(
        Uuid $id,
        User $owner,
        ?string $company,
        ?string $title,
        ?ContractType $contractType,
        ?string $location,
        ?RemoteMode $remoteMode,
        ?string $remuneration,
        ?string $offerUrl,
        ?string $notes,
        ?\DateTimeImmutable $applicationDate,
        ?\DateTimeImmutable $plannedFollowUpDate,
        ?\DateTimeImmutable $effectiveFollowUpDate,
        ?\DateTimeImmutable $firstContactDate,
        ?\DateTimeImmutable $preliminaryInterviewDate,
        ?\DateTimeImmutable $secondInterviewDate,
        ?string $hrContactName,
        ?string $businessContactName,
        ?int $subjectiveRelevance,
        TrackedJobStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        $trackedJob = new self($owner);
        $trackedJob->id = $id;
        $trackedJob->company = self::trimOrNull($company);
        $trackedJob->title = self::trimOrNull($title);
        $trackedJob->contractType = $contractType ?? ContractType::CDI;
        $trackedJob->location = self::trimOrNull($location);
        $trackedJob->remoteMode = $remoteMode;
        $trackedJob->remuneration = self::trimOrNull($remuneration);
        $trackedJob->offerUrl = self::trimOrNull($offerUrl);
        $trackedJob->notes = self::trimOrNull($notes);
        $trackedJob->applicationDate = $applicationDate;
        $trackedJob->plannedFollowUpDate = $plannedFollowUpDate;
        $trackedJob->effectiveFollowUpDate = $effectiveFollowUpDate;
        $trackedJob->firstContactDate = $firstContactDate;
        $trackedJob->preliminaryInterviewDate = $preliminaryInterviewDate;
        $trackedJob->secondInterviewDate = $secondInterviewDate;
        $trackedJob->hrContactName = self::trimOrNull($hrContactName);
        $trackedJob->businessContactName = self::trimOrNull($businessContactName);
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

    public function updateDetails(
        ?string $company,
        ?string $title,
        ?ContractType $contractType,
        ?string $location,
        ?RemoteMode $remoteMode,
        ?string $remuneration,
        ?string $offerUrl,
        ?string $notes,
    ): void {
        $this->company = self::trimOrNull($company);
        $this->title = self::trimOrNull($title);
        $this->contractType = $contractType ?? ContractType::CDI;
        $this->location = self::trimOrNull($location);
        $this->remoteMode = $remoteMode;
        $this->remuneration = self::trimOrNull($remuneration);
        $this->offerUrl = self::trimOrNull($offerUrl);
        $this->notes = self::trimOrNull($notes);
    }

    public function updateProcessDates(
        ?\DateTimeImmutable $applicationDate,
        ?\DateTimeImmutable $effectiveFollowUpDate,
        ?\DateTimeImmutable $firstContactDate,
        ?\DateTimeImmutable $preliminaryInterviewDate,
        ?\DateTimeImmutable $secondInterviewDate,
    ): void {
        $this->applicationDate = $applicationDate;
        $this->effectiveFollowUpDate = $effectiveFollowUpDate;
        $this->firstContactDate = $firstContactDate;
        $this->preliminaryInterviewDate = $preliminaryInterviewDate;
        $this->secondInterviewDate = $secondInterviewDate;
        $this->plannedFollowUpDate = $this->calculatePlannedFollowUpDate($applicationDate);
    }

    public function updateContacts(?string $hrContactName, ?string $businessContactName): void
    {
        $this->hrContactName = self::trimOrNull($hrContactName);
        $this->businessContactName = self::trimOrNull($businessContactName);
    }

    public function updateSubjectiveRelevance(?int $subjectiveRelevance): void
    {
        $this->subjectiveRelevance = $subjectiveRelevance;
    }

    public function recalculateStatus(?TrackedJobStatus $requestedFinalStatus = null): void
    {
        if ($requestedFinalStatus?->isFinal()) {
            $this->status = $requestedFinalStatus;

            return;
        }

        if ($this->status->isFinal() && $requestedFinalStatus === null) {
            return;
        }

        $this->status = $this->inferStatusFromDates();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getTitle(): ?string
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

    public function getOfferUrl(): ?string
    {
        return $this->offerUrl;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getApplicationDate(): ?\DateTimeImmutable
    {
        return $this->applicationDate;
    }

    public function getPlannedFollowUpDate(): ?\DateTimeImmutable
    {
        return $this->plannedFollowUpDate;
    }

    public function getEffectiveFollowUpDate(): ?\DateTimeImmutable
    {
        return $this->effectiveFollowUpDate;
    }

    public function getFirstContactDate(): ?\DateTimeImmutable
    {
        return $this->firstContactDate;
    }

    public function getPreliminaryInterviewDate(): ?\DateTimeImmutable
    {
        return $this->preliminaryInterviewDate;
    }

    public function getSecondInterviewDate(): ?\DateTimeImmutable
    {
        return $this->secondInterviewDate;
    }

    public function getHrContactName(): ?string
    {
        return $this->hrContactName;
    }

    public function getBusinessContactName(): ?string
    {
        return $this->businessContactName;
    }

    public function getSubjectiveRelevance(): ?int
    {
        return $this->subjectiveRelevance;
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

    private function inferStatusFromDates(): TrackedJobStatus
    {
        if ($this->secondInterviewDate !== null) {
            return TrackedJobStatus::SECOND_INTERVIEW;
        }

        if ($this->preliminaryInterviewDate !== null) {
            return TrackedJobStatus::PRELIMINARY_INTERVIEW;
        }

        if ($this->firstContactDate !== null) {
            return TrackedJobStatus::FIRST_CONTACT;
        }

        if ($this->effectiveFollowUpDate !== null) {
            return TrackedJobStatus::FOLLOW_UP_DONE;
        }

        if ($this->plannedFollowUpDate !== null) {
            return TrackedJobStatus::FOLLOW_UP_PENDING;
        }

        if ($this->applicationDate !== null) {
            return TrackedJobStatus::APPLIED;
        }

        return TrackedJobStatus::DRAFT;
    }

    private function calculatePlannedFollowUpDate(?\DateTimeImmutable $applicationDate): ?\DateTimeImmutable
    {
        return $applicationDate?->setTime(0, 0)->modify('+15 days');
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
