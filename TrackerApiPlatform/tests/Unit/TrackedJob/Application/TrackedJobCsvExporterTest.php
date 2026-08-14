<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job CSV export formatting.
 * They lock the frontend-compatible CSV contract while keeping the exporter free from HTTP concerns.
 */

use App\Security\Domain\Entity\User;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Tests\Support\Date\FixedDates;
use App\TrackedJob\Application\Export\TrackedJobCsvExporter;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;

it('exports the expected CSV header', function (): void {
    $csv = (new TrackedJobCsvExporter())->export([]);
    $lines = preg_split('/\r\n|\n|\r/', trim($csv));

    expect($lines)->not->toBeFalse()
        ->and(str_getcsv($lines[0], ';', '"', '\\'))->toBe([
            'Id',
            'Company',
            'Title',
            'Status',
            'ContractType',
            'Location',
            'RemoteMode',
            'Remuneration',
            'OfferUrl',
            'ApplicationDate',
            'PlannedFollowUpDate',
            'EffectiveFollowUpDate',
            'FirstContactDate',
            'PreliminaryInterviewDate',
            'SecondInterviewDate',
            'HrContactName',
            'BusinessContactName',
            'SubjectiveRelevance',
            'Notes',
            'CreatedAt',
            'UpdatedAt',
        ]);
});

it('serializes tracked job rows and keeps empty optional fields', function (): void {
    $owner = new User(EmailAddress::fromString('owner@example.com'));
    $trackedJob = TrackedJob::reconstitute(
        TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004'),
        $owner->getId(),
        CompanyName::fromNullable('Acme'),
        JobTitle::fromNullable('Backend Engineer'),
        ContractType::CDI,
        null,
        null,
        null,
        null,
        null,
        TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, null, null),
        null,
        null,
        null,
        TrackedJobStatus::APPLIED,
        FixedDates::april1(),
        FixedDates::april15(),
    );

    $csv = (new TrackedJobCsvExporter())->export([$trackedJob]);
    $lines = preg_split('/\r\n|\n|\r/', trim($csv));

    expect($lines)->not->toBeFalse();

    $row = str_getcsv($lines[1], ';', '"', '\\');

    expect($row[0])->toBe($trackedJob->getId()->toRfc4122())
        ->and($row[1])->toBe('Acme')
        ->and($row[2])->toBe('Backend Engineer')
        ->and($row[3])->toBe(TrackedJobStatus::APPLIED->value)
        ->and($row[4])->toBe(ContractType::CDI->value)
        ->and($row[9])->toBe(FixedDates::april1()->format(\DateTimeInterface::ATOM))
        ->and($row[10])->toBe(FixedDates::april1()->modify('+15 days')->format(\DateTimeInterface::ATOM))
        ->and($row[11])->toBe('')
        ->and($row[12])->toBe('')
        ->and($row[13])->toBe('')
        ->and($row[14])->toBe('');
});


