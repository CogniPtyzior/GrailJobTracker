<?php

declare(strict_types=1);

/*
 * Unit tests for the tracked job API mapper.
 * They protect the frontend-facing read shape without reusing legacy presenter classes.
 */

use App\Security\Domain\ValueObject\UserId;
use App\TrackedJob\Api\Mapper\TrackedJobApiMapper;
use App\TrackedJob\Application\Result\SearchTrackedJobsResult;
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

it('maps a tracked job to the frontend-compatible item output', function (): void {
    $output = (new TrackedJobApiMapper())->toItemOutput(trackedJobApiFixture());

    expect($output->item->id)->toBe('018f6d6f-0000-7000-8000-000000000004')
        ->and($output->item->company)->toBe('Acme')
        ->and($output->item->title)->toBe('Backend Engineer')
        ->and($output->item->contractType)->toBe('CDD')
        ->and($output->item->remoteMode)->toBe('HYBRID')
        ->and($output->item->offerUrl)->toBe('https://example.com/job')
        ->and($output->item->notes)->toBe('Strong fit')
        ->and($output->item->plannedFollowUpDate)->toBe('2026-04-17T00:00:00+00:00')
        ->and($output->item->hrContactName)->toBe('Jane HR')
        ->and($output->item->businessContactName)->toBe('Bob Manager')
        ->and($output->item->subjectiveRelevance)->toBe(8)
        ->and($output->item->status)->toBe('HIRED');
});

it('maps search results to the legacy collection envelope', function (): void {
    $trackedJob = trackedJobApiFixture();
    $result = new SearchTrackedJobsResult([$trackedJob], true);

    $output = (new TrackedJobApiMapper())->toCollectionOutput($result, 2, 25);

    expect($output->items)->toHaveCount(1)
        ->and($output->items[0]->id)->toBe($trackedJob->getId()->toRfc4122())
        ->and($output->page)->toBe(2)
        ->and($output->pageSize)->toBe(25)
        ->and($output->hasMore)->toBeTrue();
});

function trackedJobApiFixture(): TrackedJob
{
    return TrackedJob::reconstitute(
        TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004'),
        UserId::fromString('018f6d6f-0000-7000-8000-000000000001'),
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
}
