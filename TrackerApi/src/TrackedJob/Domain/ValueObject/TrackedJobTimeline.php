<?php

namespace App\TrackedJob\Domain\ValueObject;

use App\TrackedJob\Domain\Enum\TrackedJobStatus;

final readonly class TrackedJobTimeline
{
    private const FOLLOW_UP_DELAY_DAYS = 15;

    private function __construct(
        private ?\DateTimeImmutable $applicationDate,
        private ?\DateTimeImmutable $plannedFollowUpDate,
        private ?\DateTimeImmutable $effectiveFollowUpDate,
        private ?\DateTimeImmutable $firstContactDate,
        private ?\DateTimeImmutable $preliminaryInterviewDate,
        private ?\DateTimeImmutable $secondInterviewDate,
    ) {
    }

    public static function empty(): self
    {
        return new self(null, null, null, null, null, null);
    }

    public static function fromProcessDates(
        ?\DateTimeImmutable $applicationDate,
        ?\DateTimeImmutable $effectiveFollowUpDate,
        ?\DateTimeImmutable $firstContactDate,
        ?\DateTimeImmutable $preliminaryInterviewDate,
        ?\DateTimeImmutable $secondInterviewDate,
    ): self {
        return new self(
            $applicationDate,
            self::calculatePlannedFollowUpDate($applicationDate),
            $effectiveFollowUpDate,
            $firstContactDate,
            $preliminaryInterviewDate,
            $secondInterviewDate,
        );
    }

    public static function fromPersistedState(
        ?\DateTimeImmutable $applicationDate,
        ?\DateTimeImmutable $plannedFollowUpDate,
        ?\DateTimeImmutable $effectiveFollowUpDate,
        ?\DateTimeImmutable $firstContactDate,
        ?\DateTimeImmutable $preliminaryInterviewDate,
        ?\DateTimeImmutable $secondInterviewDate,
    ): self {
        return new self(
            $applicationDate,
            $plannedFollowUpDate,
            $effectiveFollowUpDate,
            $firstContactDate,
            $preliminaryInterviewDate,
            $secondInterviewDate,
        );
    }

    public function applicationDate(): ?\DateTimeImmutable
    {
        return $this->applicationDate;
    }

    public function plannedFollowUpDate(): ?\DateTimeImmutable
    {
        return $this->plannedFollowUpDate;
    }

    public function effectiveFollowUpDate(): ?\DateTimeImmutable
    {
        return $this->effectiveFollowUpDate;
    }

    public function firstContactDate(): ?\DateTimeImmutable
    {
        return $this->firstContactDate;
    }

    public function preliminaryInterviewDate(): ?\DateTimeImmutable
    {
        return $this->preliminaryInterviewDate;
    }

    public function secondInterviewDate(): ?\DateTimeImmutable
    {
        return $this->secondInterviewDate;
    }

    public function isFollowUpDueAt(\DateTimeImmutable $now): bool
    {
        return $this->plannedFollowUpDate !== null
            && $this->effectiveFollowUpDate === null
            && $this->plannedFollowUpDate <= $now;
    }

    public function inferStatus(): TrackedJobStatus
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

    private static function calculatePlannedFollowUpDate(?\DateTimeImmutable $applicationDate): ?\DateTimeImmutable
    {
        return $applicationDate?->setTime(0, 0)->modify(sprintf('+%d days', self::FOLLOW_UP_DELAY_DAYS));
    }
}
