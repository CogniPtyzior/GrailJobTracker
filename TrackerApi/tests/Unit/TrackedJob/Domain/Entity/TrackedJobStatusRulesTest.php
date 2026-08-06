<?php

namespace App\Tests\Unit\TrackedJob\Domain\Entity;

use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\Tests\Support\Builder\TrackedJobBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;

final class TrackedJobStatusRulesTest extends TestCase
{
    public function testRequestedFinalStatusWins(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->build();

        $trackedJob->requestStatus(TrackedJobStatus::REJECTED);

        self::assertSame(TrackedJobStatus::REJECTED, $trackedJob->getStatus());
    }

    public function testExistingFinalStatusRemainsWithoutOverride(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withStatus(TrackedJobStatus::HIRED)
            ->withApplicationDate(FixedDates::april1())
            ->build();

        $trackedJob->requestStatus();

        self::assertSame(TrackedJobStatus::HIRED, $trackedJob->getStatus());
    }

    public function testSecondInterviewHasHighestAutomaticPriority(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withEffectiveFollowUpDate(FixedDates::april5())
            ->withFirstContactDate(FixedDates::april10())
            ->withPreliminaryInterviewDate(FixedDates::april15())
            ->withSecondInterviewDate(FixedDates::april20())
            ->build();

        $trackedJob->requestStatus();

        self::assertSame(TrackedJobStatus::SECOND_INTERVIEW, $trackedJob->getStatus());
    }

    public function testPreliminaryInterviewWinsOverFirstContact(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withFirstContactDate(FixedDates::april10())
            ->withPreliminaryInterviewDate(FixedDates::april15())
            ->build();

        $trackedJob->requestStatus();

        self::assertSame(TrackedJobStatus::PRELIMINARY_INTERVIEW, $trackedJob->getStatus());
    }

    public function testFirstContactWinsOverFollowUpStatuses(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withPlannedFollowUpDate(FixedDates::april15())
            ->withEffectiveFollowUpDate(FixedDates::april10())
            ->withFirstContactDate(FixedDates::april20())
            ->build();

        $trackedJob->requestStatus();

        self::assertSame(TrackedJobStatus::FIRST_CONTACT, $trackedJob->getStatus());
    }

    public function testEffectiveFollowUpWinsOverPlannedFollowUp(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withPlannedFollowUpDate(FixedDates::april15())
            ->withEffectiveFollowUpDate(FixedDates::april10())
            ->build();

        $trackedJob->requestStatus();

        self::assertSame(TrackedJobStatus::FOLLOW_UP_DONE, $trackedJob->getStatus());
    }

    public function testPlannedFollowUpWinsOverApplication(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withPlannedFollowUpDate(FixedDates::april15())
            ->build();

        $trackedJob->requestStatus();

        self::assertSame(TrackedJobStatus::FOLLOW_UP_PENDING, $trackedJob->getStatus());
    }

    public function testApplicationDateGivesAppliedStatus(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->build();

        $trackedJob->requestStatus();

        self::assertSame(TrackedJobStatus::APPLIED, $trackedJob->getStatus());
    }

    public function testNoDatesFallsBackToDraft(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();

        $trackedJob->requestStatus();

        self::assertSame(TrackedJobStatus::DRAFT, $trackedJob->getStatus());
    }
}
