<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job timeline rules.
 * They protect follow-up date calculation, due checks and automatic status inference.
 */

use App\Tests\Support\Date\FixedDates;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;

it('computes planned follow-up from the application date at UTC midnight', function (): void {
    $timeline = TrackedJobTimeline::fromProcessDates(
        new DateTimeImmutable('2026-04-01T14:30:00+00:00'),
        null,
        null,
        null,
        null,
    );

    expect($timeline->plannedFollowUpDate()?->format('c'))->toBe('2026-04-16T00:00:00+00:00');
});

it('clears planned follow-up when there is no application date', function (): void {
    $timeline = TrackedJobTimeline::fromProcessDates(null, null, null, null, null);

    expect($timeline->applicationDate())->toBeNull()
        ->and($timeline->plannedFollowUpDate())->toBeNull();
});

it('preserves persisted planned follow-up dates', function (): void {
    $plannedFollowUpDate = new DateTimeImmutable('2026-04-30T16:45:00+00:00');

    $timeline = TrackedJobTimeline::fromPersistedState(FixedDates::april1(), $plannedFollowUpDate, null, null, null, null);

    expect($timeline->plannedFollowUpDate())->toBe($plannedFollowUpDate);
});

it('detects due follow-up only when planned and not completed', function (): void {
    $timeline = TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, null, null);

    expect($timeline->isFollowUpDueAt(new DateTimeImmutable('2026-04-15T23:59:59+00:00')))->toBeFalse()
        ->and($timeline->isFollowUpDueAt(new DateTimeImmutable('2026-04-16T00:00:00+00:00')))->toBeTrue()
        ->and(TrackedJobTimeline::fromProcessDates(FixedDates::april1(), FixedDates::april10(), null, null, null)
            ->isFollowUpDueAt(FixedDates::april20()))->toBeFalse();
});

it('infers status from the most advanced process date', function (): void {
    expect(TrackedJobTimeline::empty()->inferStatus())->toBe(TrackedJobStatus::DRAFT)
        ->and(TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, null, null)->inferStatus())
        ->toBe(TrackedJobStatus::FOLLOW_UP_PENDING)
        ->and(TrackedJobTimeline::fromProcessDates(FixedDates::april1(), FixedDates::april5(), null, null, null)->inferStatus())
        ->toBe(TrackedJobStatus::FOLLOW_UP_DONE)
        ->and(TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, FixedDates::april10(), null, null)->inferStatus())
        ->toBe(TrackedJobStatus::FIRST_CONTACT)
        ->and(TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, FixedDates::april15(), null)->inferStatus())
        ->toBe(TrackedJobStatus::PRELIMINARY_INTERVIEW)
        ->and(TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, null, FixedDates::april20())->inferStatus())
        ->toBe(TrackedJobStatus::SECOND_INTERVIEW);
});
