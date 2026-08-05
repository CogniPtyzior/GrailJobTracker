<?php

namespace App\Tests\Support\Builder;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;

final class TrackedJobBuilder
{
    private ?User $owner = null;
    private ?string $company = 'Acme';
    private ?string $title = 'Backend Engineer';
    private ?ContractType $contractType = ContractType::CDI;
    private ?string $location = 'Paris';
    private ?RemoteMode $remoteMode = RemoteMode::HYBRID;
    private ?string $remuneration = '60k';
    private ?string $offerUrl = 'https://example.com/job';
    private ?string $notes = 'Interesting role';
    private ?\DateTimeImmutable $applicationDate = null;
    private ?\DateTimeImmutable $plannedFollowUpDate = null;
    private ?\DateTimeImmutable $effectiveFollowUpDate = null;
    private ?\DateTimeImmutable $firstContactDate = null;
    private ?\DateTimeImmutable $preliminaryInterviewDate = null;
    private ?\DateTimeImmutable $secondInterviewDate = null;
    private ?string $hrContactName = 'Jane HR';
    private ?string $businessContactName = 'Bob Manager';
    private ?int $subjectiveRelevance = 8;
    private TrackedJobStatus $status = TrackedJobStatus::DRAFT;

    public static function aTrackedJob(): self
    {
        return new self();
    }

    public function ownedBy(User $owner): self
    {
        $clone = clone $this;
        $clone->owner = $owner;

        return $clone;
    }

    public function withCompany(?string $company): self
    {
        $clone = clone $this;
        $clone->company = $company;

        return $clone;
    }

    public function withTitle(?string $title): self
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    public function withContractType(?ContractType $contractType): self
    {
        $clone = clone $this;
        $clone->contractType = $contractType;

        return $clone;
    }

    public function withRemoteMode(?RemoteMode $remoteMode): self
    {
        $clone = clone $this;
        $clone->remoteMode = $remoteMode;

        return $clone;
    }

    public function withApplicationDate(?\DateTimeImmutable $applicationDate): self
    {
        $clone = clone $this;
        $clone->applicationDate = $applicationDate;

        return $clone;
    }

    public function withPlannedFollowUpDate(?\DateTimeImmutable $plannedFollowUpDate): self
    {
        $clone = clone $this;
        $clone->plannedFollowUpDate = $plannedFollowUpDate;

        return $clone;
    }

    public function withEffectiveFollowUpDate(?\DateTimeImmutable $effectiveFollowUpDate): self
    {
        $clone = clone $this;
        $clone->effectiveFollowUpDate = $effectiveFollowUpDate;

        return $clone;
    }

    public function withFirstContactDate(?\DateTimeImmutable $firstContactDate): self
    {
        $clone = clone $this;
        $clone->firstContactDate = $firstContactDate;

        return $clone;
    }

    public function withPreliminaryInterviewDate(?\DateTimeImmutable $preliminaryInterviewDate): self
    {
        $clone = clone $this;
        $clone->preliminaryInterviewDate = $preliminaryInterviewDate;

        return $clone;
    }

    public function withSecondInterviewDate(?\DateTimeImmutable $secondInterviewDate): self
    {
        $clone = clone $this;
        $clone->secondInterviewDate = $secondInterviewDate;

        return $clone;
    }

    public function withStatus(TrackedJobStatus $status): self
    {
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }

    public function build(): TrackedJob
    {
        $trackedJob = new TrackedJob($this->owner ?? UserBuilder::aUser()->build());
        $trackedJob->updateDetails(
            $this->company,
            $this->title,
            $this->contractType,
            $this->location,
            $this->remoteMode,
            $this->remuneration,
            $this->offerUrl,
            $this->notes,
        );
        $trackedJob->updateProcessDates(
            $this->applicationDate,
            $this->effectiveFollowUpDate,
            $this->firstContactDate,
            $this->preliminaryInterviewDate,
            $this->secondInterviewDate,
        );
        $trackedJob->updateContacts($this->hrContactName, $this->businessContactName);
        $trackedJob->updateSubjectiveRelevance($this->subjectiveRelevance);

        // The builder can represent legacy or artificial states that are not produced by current domain methods.
        $this->forceValue($trackedJob, 'plannedFollowUpDate', $this->plannedFollowUpDate);

        $trackedJob->recalculateStatus($this->status);

        return $trackedJob;
    }

    private function forceValue(TrackedJob $trackedJob, string $property, mixed $value): void
    {
        $reflectionProperty = new \ReflectionProperty($trackedJob, $property);
        $reflectionProperty->setValue($trackedJob, $value);
    }
}
