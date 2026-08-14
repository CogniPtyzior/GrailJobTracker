<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job status rules.
 * They protect final status overrides and automatic timeline priority ordering.
 */

use App\Security\Domain\ValueObject\UserId;
use App\Tests\Support\Date\FixedDates;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;

function trackedJobWithTimeline(
    TrackedJobTimeline $timeline,
    TrackedJobStatus $status = TrackedJobStatus::DRAFT,
): TrackedJob {
    $trackedJob = TrackedJob::reconstitute(
        TrackedJobId::new(),
        UserId::fromString('018f6d6f-0000-7000-8000-000000000001'),
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        $timeline,
        null,
        null,
        null,
        $status,
        new DateTimeImmutable('2026-04-01T00:00:00+00:00'),
        new DateTimeImmutable('2026-04-01T00:00:00+00:00'),
    );

    $trackedJob->requestStatus();

    return $trackedJob;
}

it('lets requested final status win', function (): void {
    $trackedJob = trackedJobWithTimeline(TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, null, null));

    $trackedJob->requestStatus(TrackedJobStatus::REJECTED);

    expect($trackedJob->getStatus())->toBe(TrackedJobStatus::REJECTED);
});

it('keeps an existing final status without explicit override', function (): void {
    $trackedJob = trackedJobWithTimeline(
        TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, null, null),
        TrackedJobStatus::HIRED,
    );

    expect($trackedJob->getStatus())->toBe(TrackedJobStatus::HIRED);
});

it('infers statuses from the expected timeline priority', function (
    TrackedJobTimeline $timeline,
    TrackedJobStatus $expectedStatus,
): void {
    $trackedJob = trackedJobWithTimeline($timeline);

    expect($trackedJob->getStatus())->toBe($expectedStatus);
})->with([
    [
        TrackedJobTimeline::fromProcessDates(
            FixedDates::april1(),
            FixedDates::april5(),
            FixedDates::april10(),
            FixedDates::april15(),
            FixedDates::april20(),
        ),
        TrackedJobStatus::SECOND_INTERVIEW,
    ],
    [
        TrackedJobTimeline::fromProcessDates(
            FixedDates::april1(),
            null,
            FixedDates::april10(),
            FixedDates::april15(),
            null,
        ),
        TrackedJobStatus::PRELIMINARY_INTERVIEW,
    ],
    [
        TrackedJobTimeline::fromProcessDates(
            FixedDates::april1(),
            FixedDates::april10(),
            FixedDates::april20(),
            null,
            null,
        ),
        TrackedJobStatus::FIRST_CONTACT,
    ],
    [
        TrackedJobTimeline::fromProcessDates(FixedDates::april1(), FixedDates::april10(), null, null, null),
        TrackedJobStatus::FOLLOW_UP_DONE,
    ],
    [
        TrackedJobTimeline::fromPersistedState(FixedDates::april1(), FixedDates::april15(), null, null, null, null),
        TrackedJobStatus::FOLLOW_UP_PENDING,
    ],
    [TrackedJobTimeline::fromPersistedState(FixedDates::april1(), null, null, null, null, null), TrackedJobStatus::APPLIED],
    [TrackedJobTimeline::empty(), TrackedJobStatus::DRAFT],
]);