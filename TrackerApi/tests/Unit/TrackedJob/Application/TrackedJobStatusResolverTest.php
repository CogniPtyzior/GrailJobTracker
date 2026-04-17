<?php

namespace App\Tests\Unit\TrackedJob\Application;

use App\TrackedJob\Application\TrackedJobStatusResolver;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\Tests\Support\Builder\TrackedJobBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;

final class TrackedJobStatusResolverTest extends TestCase
{
    private TrackedJobStatusResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TrackedJobStatusResolver();
    }

    public function testRequestedFinalStatusWins(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->build();

        $this->resolver->recalculate($trackedJob, TrackedJobStatus::REJECTED);

        self::assertSame(TrackedJobStatus::REJECTED, $trackedJob->getStatus());
    }

    public function testExistingFinalStatusRemainsWithoutOverride(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withStatus(TrackedJobStatus::HIRED)
            ->withApplicationDate(FixedDates::april1())
            ->build();

        $this->resolver->recalculate($trackedJob);

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

        $this->resolver->recalculate($trackedJob);

        self::assertSame(TrackedJobStatus::SECOND_INTERVIEW, $trackedJob->getStatus());
    }

    public function testPreliminaryInterviewWinsOverFirstContact(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withFirstContactDate(FixedDates::april10())
            ->withPreliminaryInterviewDate(FixedDates::april15())
            ->build();

        $this->resolver->recalculate($trackedJob);

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

        $this->resolver->recalculate($trackedJob);

        self::assertSame(TrackedJobStatus::FIRST_CONTACT, $trackedJob->getStatus());
    }

    public function testEffectiveFollowUpWinsOverPlannedFollowUp(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withPlannedFollowUpDate(FixedDates::april15())
            ->withEffectiveFollowUpDate(FixedDates::april10())
            ->build();

        $this->resolver->recalculate($trackedJob);

        self::assertSame(TrackedJobStatus::FOLLOW_UP_DONE, $trackedJob->getStatus());
    }

    public function testPlannedFollowUpWinsOverApplication(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withPlannedFollowUpDate(FixedDates::april15())
            ->build();

        $this->resolver->recalculate($trackedJob);

        self::assertSame(TrackedJobStatus::FOLLOW_UP_PENDING, $trackedJob->getStatus());
    }

    public function testApplicationDateGivesAppliedStatus(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->build();

        $this->resolver->recalculate($trackedJob);

        self::assertSame(TrackedJobStatus::APPLIED, $trackedJob->getStatus());
    }

    public function testNoDatesFallsBackToDraft(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();

        $this->resolver->recalculate($trackedJob);

        self::assertSame(TrackedJobStatus::DRAFT, $trackedJob->getStatus());
    }
}