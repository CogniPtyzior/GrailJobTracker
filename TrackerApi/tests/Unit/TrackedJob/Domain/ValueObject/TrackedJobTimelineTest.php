<?php

namespace App\Tests\Unit\TrackedJob\Domain\ValueObject;

use App\Tests\Support\Date\FixedDates;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;
use PHPUnit\Framework\TestCase;

final class TrackedJobTimelineTest extends TestCase
{
    public function testFromProcessDatesComputesPlannedFollowUpFromApplicationDate(): void
    {
        $timeline = TrackedJobTimeline::fromProcessDates(
            new \DateTimeImmutable('2026-04-01T14:30:00+00:00'),
            null,
            null,
            null,
            null,
        );

        self::assertSame('2026-04-16T00:00:00+00:00', $timeline->plannedFollowUpDate()?->format('c'));
    }

    public function testFromProcessDatesClearsPlannedFollowUpWithoutApplicationDate(): void
    {
        $timeline = TrackedJobTimeline::fromProcessDates(null, null, null, null, null);

        self::assertNull($timeline->applicationDate());
        self::assertNull($timeline->plannedFollowUpDate());
    }

    public function testFromPersistedStatePreservesStoredPlannedFollowUpDate(): void
    {
        $plannedFollowUpDate = new \DateTimeImmutable('2026-04-30T16:45:00+00:00');

        $timeline = TrackedJobTimeline::fromPersistedState(
            FixedDates::april1(),
            $plannedFollowUpDate,
            null,
            null,
            null,
            null,
        );

        self::assertSame($plannedFollowUpDate, $timeline->plannedFollowUpDate());
    }

    public function testIsFollowUpDueAtRequiresPlannedDateWithoutCompletion(): void
    {
        $timeline = TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, null, null);

        self::assertFalse($timeline->isFollowUpDueAt(new \DateTimeImmutable('2026-04-15T23:59:59+00:00')));
        self::assertTrue($timeline->isFollowUpDueAt(new \DateTimeImmutable('2026-04-16T00:00:00+00:00')));
    }

    public function testCompletedFollowUpIsNotDue(): void
    {
        $timeline = TrackedJobTimeline::fromProcessDates(
            FixedDates::april1(),
            FixedDates::april10(),
            null,
            null,
            null,
        );

        self::assertFalse($timeline->isFollowUpDueAt(new \DateTimeImmutable('2026-04-20T00:00:00+00:00')));
    }

    public function testInferStatusFollowsProcessPriority(): void
    {
        $timeline = TrackedJobTimeline::fromProcessDates(
            FixedDates::april1(),
            FixedDates::april5(),
            FixedDates::april10(),
            FixedDates::april15(),
            FixedDates::april20(),
        );

        self::assertSame(TrackedJobStatus::SECOND_INTERVIEW, $timeline->inferStatus());
    }
}

