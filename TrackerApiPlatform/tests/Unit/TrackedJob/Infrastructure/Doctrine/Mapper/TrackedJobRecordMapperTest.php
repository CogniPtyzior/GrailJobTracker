<?php

declare(strict_types=1);

/*
 * Unit tests for the tracked job Doctrine mapper.
 * They protect the boundary between the domain aggregate and the preserved tracked_jobs table record.
 */

use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
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
use App\TrackedJob\Infrastructure\Doctrine\Entity\TrackedJobRecord;
use App\TrackedJob\Infrastructure\Doctrine\Mapper\TrackedJobRecordMapper;
use Symfony\Component\Uid\Uuid;

it('maps a Doctrine record to the tracked job domain aggregate', function (): void {
    $record = trackedJobRecordFixture();

    $trackedJob = (new TrackedJobRecordMapper())->toDomain($record);

    expect($trackedJob->getId()->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000004')
        ->and($trackedJob->ownerId()->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000001')
        ->and($trackedJob->company()?->value())->toBe('Acme')
        ->and($trackedJob->title()?->value())->toBe('Backend Engineer')
        ->and($trackedJob->getContractType())->toBe(ContractType::CDD)
        ->and($trackedJob->getLocation())->toBe('Paris')
        ->and($trackedJob->getRemoteMode())->toBe(RemoteMode::HYBRID)
        ->and($trackedJob->getRemuneration())->toBe('60k')
        ->and($trackedJob->offerUrl()?->value())->toBe('https://example.com/job')
        ->and($trackedJob->notes()?->value())->toBe('Strong fit')
        ->and($trackedJob->timeline()->plannedFollowUpDate()?->format('c'))->toBe('2026-04-17T00:00:00+00:00')
        ->and($trackedJob->hrContactName()?->value())->toBe('Jane HR')
        ->and($trackedJob->businessContactName()?->value())->toBe('Bob Manager')
        ->and($trackedJob->getSubjectiveRelevance())->toBe(8)
        ->and($trackedJob->getStatus())->toBe(TrackedJobStatus::HIRED);
});

it('updates a Doctrine record from the tracked job domain aggregate', function (): void {
    $ownerRecord = userRecordFixture();
    $record = new TrackedJobRecord();
    $trackedJob = TrackedJob::reconstitute(
        TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004'),
        \App\Security\Domain\ValueObject\UserId::fromString('018f6d6f-0000-7000-8000-000000000001'),
        CompanyName::fromNullable('Acme'),
        JobTitle::fromNullable('Backend Engineer'),
        ContractType::CDD,
        'Paris',
        RemoteMode::HYBRID,
        '60k',
        OfferUrl::fromNullable('https://example.com/job'),
        TrackedJobNotes::fromNullable('Strong fit'),
        TrackedJobTimeline::fromPersistedState(
            new DateTimeImmutable('2026-04-02T09:00:00+00:00'),
            new DateTimeImmutable('2026-04-17T00:00:00+00:00'),
            new DateTimeImmutable('2026-04-10T11:00:00+00:00'),
            new DateTimeImmutable('2026-04-12T11:00:00+00:00'),
            new DateTimeImmutable('2026-04-15T11:00:00+00:00'),
            new DateTimeImmutable('2026-04-18T11:00:00+00:00'),
        ),
        ContactName::fromNullable('Jane HR'),
        ContactName::fromNullable('Bob Manager'),
        SubjectiveRelevance::fromInt(8),
        TrackedJobStatus::HIRED,
        new DateTimeImmutable('2026-04-01T10:00:00+00:00'),
        new DateTimeImmutable('2026-04-20T12:30:00+00:00'),
    );

    (new TrackedJobRecordMapper())->updateRecord($trackedJob, $record, $ownerRecord);

    expect($record->getId()->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000004')
        ->and($record->getOwner())->toBe($ownerRecord)
        ->and($record->getCompany())->toBe('Acme')
        ->and($record->getTitle())->toBe('Backend Engineer')
        ->and($record->getContractType())->toBe(ContractType::CDD)
        ->and($record->getRemoteMode())->toBe(RemoteMode::HYBRID)
        ->and($record->getPlannedFollowUpDate()?->format('c'))->toBe('2026-04-17T00:00:00+00:00')
        ->and($record->getStatus())->toBe(TrackedJobStatus::HIRED);
});

function trackedJobRecordFixture(): TrackedJobRecord
{
    $record = new TrackedJobRecord();
    $record->setId(Uuid::fromString('018f6d6f-0000-7000-8000-000000000004'));
    $record->setOwner(userRecordFixture());
    $record->setCompany('  Acme  ');
    $record->setTitle('  Backend Engineer  ');
    $record->setContractType(ContractType::CDD);
    $record->setLocation('Paris');
    $record->setRemoteMode(RemoteMode::HYBRID);
    $record->setRemuneration('60k');
    $record->setOfferUrl('https://example.com/job');
    $record->setNotes('Strong fit');
    $record->setApplicationDate(new DateTimeImmutable('2026-04-02T09:00:00+00:00'));
    $record->setPlannedFollowUpDate(new DateTimeImmutable('2026-04-17T00:00:00+00:00'));
    $record->setEffectiveFollowUpDate(new DateTimeImmutable('2026-04-10T11:00:00+00:00'));
    $record->setFirstContactDate(new DateTimeImmutable('2026-04-12T11:00:00+00:00'));
    $record->setPreliminaryInterviewDate(new DateTimeImmutable('2026-04-15T11:00:00+00:00'));
    $record->setSecondInterviewDate(new DateTimeImmutable('2026-04-18T11:00:00+00:00'));
    $record->setHrContactName('Jane HR');
    $record->setBusinessContactName('Bob Manager');
    $record->setSubjectiveRelevance(8);
    $record->setStatus(TrackedJobStatus::HIRED);
    $record->setCreatedAt(new DateTimeImmutable('2026-04-01T10:00:00+00:00'));
    $record->setUpdatedAt(new DateTimeImmutable('2026-04-20T12:30:00+00:00'));

    return $record;
}

function userRecordFixture(): UserRecord
{
    $record = new UserRecord();
    $record->setId(Uuid::fromString('018f6d6f-0000-7000-8000-000000000001'));
    $record->setEmail('owner@example.com');
    $record->setNormalizedEmail('owner@example.com');
    $record->setRoles(['ROLE_USER']);
    $record->setCreatedAt(new DateTimeImmutable('2026-04-01T00:00:00+00:00'));

    return $record;
}
