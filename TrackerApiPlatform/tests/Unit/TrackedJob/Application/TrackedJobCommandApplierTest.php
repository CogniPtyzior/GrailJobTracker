<?php

declare(strict_types=1);

/*
 * Unit tests for command application on tracked jobs.
 * They verify application orchestration before API processors start creating commands.
 */

use App\Security\Domain\ValueObject\UserId;
use App\TrackedJob\Application\Command\TrackedJobCommand;
use App\TrackedJob\Application\Date\TrackedJobDateParser;
use App\TrackedJob\Application\Service\TrackedJobCommandApplier;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\ContactName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\OfferUrl;
use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;

it('hydrates a full command into a tracked job', function (): void {
    $trackedJob = TrackedJob::openFor(UserId::fromString('018f6d6f-0000-7000-8000-000000000001'));

    (new TrackedJobCommandApplier())->apply($trackedJob, fullTrackedJobCommand());

    expect($trackedJob->company()?->value())->toBe('Acme')
        ->and($trackedJob->title()?->value())->toBe('Backend Engineer')
        ->and($trackedJob->getContractType())->toBe(ContractType::CDD)
        ->and($trackedJob->getLocation())->toBe('Paris')
        ->and($trackedJob->getRemoteMode())->toBe(RemoteMode::FULL)
        ->and($trackedJob->getRemuneration())->toBe('60k')
        ->and($trackedJob->offerUrl()?->value())->toBe('https://example.com/job')
        ->and($trackedJob->notes()?->value())->toBe('Strong fit')
        ->and($trackedJob->hrContactName()?->value())->toBe('Jane HR')
        ->and($trackedJob->businessContactName()?->value())->toBe('Bob Manager')
        ->and($trackedJob->getSubjectiveRelevance())->toBe(9)
        ->and($trackedJob->getStatus())->toBe(TrackedJobStatus::SECOND_INTERVIEW);
});

it('applies null command values and defaults contract type', function (): void {
    $trackedJob = TrackedJob::openFor(UserId::fromString('018f6d6f-0000-7000-8000-000000000001'));

    (new TrackedJobCommandApplier())->apply($trackedJob, new TrackedJobCommand());

    expect($trackedJob->company())->toBeNull()
        ->and($trackedJob->title())->toBeNull()
        ->and($trackedJob->getContractType())->toBe(ContractType::CDI)
        ->and($trackedJob->getRemoteMode())->toBeNull()
        ->and($trackedJob->getLocation())->toBeNull()
        ->and($trackedJob->getRemuneration())->toBeNull()
        ->and($trackedJob->offerUrl())->toBeNull()
        ->and($trackedJob->notes())->toBeNull()
        ->and($trackedJob->hrContactName())->toBeNull()
        ->and($trackedJob->businessContactName())->toBeNull()
        ->and($trackedJob->getSubjectiveRelevance())->toBeNull();
});

it('respects explicit final status in commands', function (): void {
    $trackedJob = TrackedJob::openFor(UserId::fromString('018f6d6f-0000-7000-8000-000000000001'));
    $command = new TrackedJobCommand(
        company: CompanyName::fromNullable('Acme'),
        title: JobTitle::fromNullable('Backend Engineer'),
        applicationDate: TrackedJobDateParser::parseNullable('2026-04-01T09:00:00+00:00'),
        status: TrackedJobStatus::WITHDRAWN,
    );

    (new TrackedJobCommandApplier())->apply($trackedJob, $command);

    expect($trackedJob->getStatus())->toBe(TrackedJobStatus::WITHDRAWN);
});

function fullTrackedJobCommand(): TrackedJobCommand
{
    return new TrackedJobCommand(
        company: CompanyName::fromNullable('Acme'),
        title: JobTitle::fromNullable('Backend Engineer'),
        contractType: ContractType::CDD,
        location: 'Paris',
        remoteMode: RemoteMode::FULL,
        remuneration: '60k',
        offerUrl: OfferUrl::fromNullable('https://example.com/job'),
        notes: TrackedJobNotes::fromNullable('Strong fit'),
        applicationDate: TrackedJobDateParser::parseNullable('2026-04-01T09:00:00+00:00'),
        effectiveFollowUpDate: TrackedJobDateParser::parseNullable('2026-04-10T09:00:00+00:00'),
        firstContactDate: TrackedJobDateParser::parseNullable('2026-04-11T09:00:00+00:00'),
        preliminaryInterviewDate: TrackedJobDateParser::parseNullable('2026-04-15T09:00:00+00:00'),
        secondInterviewDate: TrackedJobDateParser::parseNullable('2026-04-20T09:00:00+00:00'),
        hrContactName: ContactName::fromNullable('Jane HR'),
        businessContactName: ContactName::fromNullable('Bob Manager'),
        subjectiveRelevance: 9,
    );
}
