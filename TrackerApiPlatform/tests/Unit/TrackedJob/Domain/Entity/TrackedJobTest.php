<?php

declare(strict_types=1);

/*
 * Unit tests for the tracked job aggregate.
 * They preserve normalization, reconstitution and update behavior before API and persistence adapters are added.
 */

use App\Security\Domain\ValueObject\UserId;
use App\Tests\Support\Date\FixedDates;
use App\TrackedJob\Domain\Entity\TrackedJob;
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

it('initializes default tracked job state', function (): void {
    $trackedJob = new TrackedJob(UserId::fromString('018f6d6f-0000-7000-8000-000000000001'));

    expect($trackedJob->getStatus())->toBe(TrackedJobStatus::DRAFT)
        ->and($trackedJob->getContractType())->toBe(ContractType::CDI)
        ->and($trackedJob->getId())->toBeInstanceOf(TrackedJobId::class)
        ->and($trackedJob->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($trackedJob->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

it('normalizes position fields and keeps enum values', function (): void {
    $trackedJob = TrackedJob::openFor(UserId::fromString('018f6d6f-0000-7000-8000-000000000001'));

    $trackedJob->updatePosition(
        CompanyName::fromNullable('  Acme  '),
        JobTitle::fromNullable('  Backend Engineer  '),
        ContractType::CDD,
        '  Paris  ',
        RemoteMode::FULL,
        '  60k  ',
        OfferUrl::fromNullable('  https://example.com/job  '),
        TrackedJobNotes::fromNullable('  Strong fit  '),
    );

    expect($trackedJob->company()?->value())->toBe('Acme')
        ->and($trackedJob->title()?->value())->toBe('Backend Engineer')
        ->and($trackedJob->getContractType())->toBe(ContractType::CDD)
        ->and($trackedJob->getLocation())->toBe('Paris')
        ->and($trackedJob->getRemoteMode())->toBe(RemoteMode::FULL)
        ->and($trackedJob->getRemuneration())->toBe('60k')
        ->and($trackedJob->offerUrl()?->value())->toBe('https://example.com/job')
        ->and($trackedJob->notes()?->value())->toBe('Strong fit');
});

it('converts blank position strings to null and defaults contract type', function (): void {
    $trackedJob = TrackedJob::openFor(UserId::fromString('018f6d6f-0000-7000-8000-000000000001'));

    $trackedJob->updatePosition(
        CompanyName::fromNullable('   '),
        JobTitle::fromNullable('   '),
        null,
        '   ',
        null,
        '   ',
        OfferUrl::fromNullable('   '),
        TrackedJobNotes::fromNullable('   '),
    );

    expect($trackedJob->company())->toBeNull()
        ->and($trackedJob->title())->toBeNull()
        ->and($trackedJob->getContractType())->toBe(ContractType::CDI)
        ->and($trackedJob->getLocation())->toBeNull()
        ->and($trackedJob->getRemoteMode())->toBeNull()
        ->and($trackedJob->getRemuneration())->toBeNull()
        ->and($trackedJob->offerUrl())->toBeNull()
        ->and($trackedJob->notes())->toBeNull();
});

it('updates timeline and recomputes non-final statuses', function (): void {
    $trackedJob = TrackedJob::openFor(UserId::fromString('018f6d6f-0000-7000-8000-000000000001'));

    $trackedJob->updateTimeline(TrackedJobTimeline::fromProcessDates(
        new DateTimeImmutable('2026-04-01T14:30:00+00:00'),
        FixedDates::april5(),
        FixedDates::april10(),
        FixedDates::april15(),
        FixedDates::april20(),
    ));

    expect($trackedJob->timeline()->plannedFollowUpDate()?->format('c'))->toBe('2026-04-16T00:00:00+00:00')
        ->and($trackedJob->getStatus())->toBe(TrackedJobStatus::SECOND_INTERVIEW);
});

it('updates contacts and subjective relevance', function (): void {
    $trackedJob = TrackedJob::openFor(UserId::fromString('018f6d6f-0000-7000-8000-000000000001'));

    $trackedJob->updateContacts(ContactName::fromNullable('  Jane HR  '), ContactName::fromNullable('   '));
    $trackedJob->updateRelevance(SubjectiveRelevance::fromInt(8));

    expect($trackedJob->hrContactName()?->value())->toBe('Jane HR')
        ->and($trackedJob->businessContactName())->toBeNull()
        ->and($trackedJob->getSubjectiveRelevance())->toBe(8);

    $trackedJob->updateRelevance(null);

    expect($trackedJob->getSubjectiveRelevance())->toBeNull();
});

it('reconstitutes persisted state without recomputing status or timeline', function (): void {
    $id = TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004');
    $ownerId = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');
    $createdAt = new DateTimeImmutable('2026-04-01T10:00:00+00:00');
    $updatedAt = new DateTimeImmutable('2026-04-20T12:30:00+00:00');
    $plannedFollowUpDate = new DateTimeImmutable('2026-04-17T00:00:00+00:00');
    $timeline = TrackedJobTimeline::fromPersistedState(
        FixedDates::april1(),
        $plannedFollowUpDate,
        FixedDates::april10(),
        FixedDates::april15(),
        FixedDates::april20(),
        null,
    );

    $trackedJob = TrackedJob::reconstitute(
        $id,
        $ownerId,
        CompanyName::fromNullable('  Acme  '),
        JobTitle::fromNullable('  Backend Engineer  '),
        ContractType::CDD,
        '  Paris  ',
        RemoteMode::HYBRID,
        '  60k  ',
        OfferUrl::fromNullable('  https://example.com/job  '),
        TrackedJobNotes::fromNullable('  Strong fit  '),
        $timeline,
        ContactName::fromNullable('  Jane HR  '),
        ContactName::fromNullable('   '),
        SubjectiveRelevance::fromInt(8),
        TrackedJobStatus::HIRED,
        $createdAt,
        $updatedAt,
    );

    expect($trackedJob->getId())->toBe($id)
        ->and($trackedJob->ownerId()->equals($ownerId))->toBeTrue()
        ->and($trackedJob->company()?->value())->toBe('Acme')
        ->and($trackedJob->title()?->value())->toBe('Backend Engineer')
        ->and($trackedJob->getContractType())->toBe(ContractType::CDD)
        ->and($trackedJob->getLocation())->toBe('Paris')
        ->and($trackedJob->getRemoteMode())->toBe(RemoteMode::HYBRID)
        ->and($trackedJob->getRemuneration())->toBe('60k')
        ->and($trackedJob->offerUrl()?->value())->toBe('https://example.com/job')
        ->and($trackedJob->notes()?->value())->toBe('Strong fit')
        ->and($trackedJob->timeline()->plannedFollowUpDate())->toBe($plannedFollowUpDate)
        ->and($trackedJob->hrContactName()?->value())->toBe('Jane HR')
        ->and($trackedJob->businessContactName())->toBeNull()
        ->and($trackedJob->getSubjectiveRelevance())->toBe(8)
        ->and($trackedJob->getStatus())->toBe(TrackedJobStatus::HIRED)
        ->and($trackedJob->getCreatedAt())->toBe($createdAt)
        ->and($trackedJob->getUpdatedAt())->toBe($updatedAt);
});
