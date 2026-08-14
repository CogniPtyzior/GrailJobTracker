<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job application use cases.
 * They verify orchestration through repository ports without Doctrine or API Platform.
 */

use App\Security\Domain\Entity\User;
use App\Tests\Support\Fake\InMemoryTrackedJobRepository;
use App\TrackedJob\Application\Command\TrackedJobCommand;
use App\TrackedJob\Application\Export\TrackedJobCsvExporter;
use App\TrackedJob\Application\Factory\TrackedJobFactory;
use App\TrackedJob\Application\Service\TrackedJobCommandApplier;
use App\TrackedJob\Application\UseCase\CreateTrackedJob;
use App\TrackedJob\Application\Input\ExportTrackedJobsInput;
use App\TrackedJob\Application\UseCase\DeleteTrackedJob;
use App\TrackedJob\Application\UseCase\ExportTrackedJobsCsv;
use App\TrackedJob\Application\UseCase\GetTrackedJob;
use App\TrackedJob\Application\UseCase\SearchTrackedJobs;
use App\TrackedJob\Application\UseCase\SuggestTrackedJobCompanies;
use App\TrackedJob\Application\UseCase\UpdateTrackedJob;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\Shared\Domain\ValueObject\EmailAddress;

it('creates a tracked job for the owner and persists it', function (): void {
    $repository = new InMemoryTrackedJobRepository();
    $owner = new User(EmailAddress::fromString('owner@example.com'));
    $command = new TrackedJobCommand(
        company: CompanyName::fromNullable('Acme'),
        title: JobTitle::fromNullable('Backend Engineer'),
        contractType: ContractType::CDD,
    );

    $trackedJob = (new CreateTrackedJob(new TrackedJobFactory(), new TrackedJobCommandApplier(), $repository))
        ->handle($owner, $command);

    expect($trackedJob->ownerId()->equals($owner->getId()))->toBeTrue()
        ->and($trackedJob->company()?->value())->toBe('Acme')
        ->and($trackedJob->getContractType())->toBe(ContractType::CDD)
        ->and($repository->saveCalls)->toBe(1)
        ->and($repository->flushCalls)->toBe(1);
});

it('updates an existing tracked job and persists changes', function (): void {
    $repository = new InMemoryTrackedJobRepository();
    $trackedJob = TrackedJob::openFor((new User(EmailAddress::fromString('owner@example.com')))->getId());
    $command = new TrackedJobCommand(company: CompanyName::fromNullable('Updated'));

    $updated = (new UpdateTrackedJob(new TrackedJobCommandApplier(), $repository))->handle($trackedJob, $command);

    expect($updated)->toBe($trackedJob)
        ->and($updated->company()?->value())->toBe('Updated')
        ->and($repository->saveCalls)->toBe(1)
        ->and($repository->flushCalls)->toBe(1);
});

it('loads a tracked job only for its owner', function (): void {
    $repository = new InMemoryTrackedJobRepository();
    $owner = new User(EmailAddress::fromString('owner@example.com'));
    $otherOwner = new User(EmailAddress::fromString('other@example.com'));
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);

    $useCase = new GetTrackedJob($repository);

    expect($useCase->handle($trackedJob->getId(), $owner))->toBe($trackedJob)
        ->and($useCase->handle($trackedJob->getId(), $otherOwner))->toBeNull();
});

it('deletes a tracked job through the repository port', function (): void {
    $repository = new InMemoryTrackedJobRepository();
    $trackedJob = TrackedJob::openFor((new User(EmailAddress::fromString('owner@example.com')))->getId());

    (new DeleteTrackedJob($repository))->handle($trackedJob);

    expect($repository->removeCalls)->toBe(1)
        ->and($repository->flushCalls)->toBe(1);
});

it('searches tracked jobs for the owner and returns pagination metadata', function (): void {
    $repository = new InMemoryTrackedJobRepository();
    $owner = new User(EmailAddress::fromString('owner@example.com'));
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);
    $repository->hasMore = true;

    $result = (new SearchTrackedJobs($repository))->handle($owner, ['status' => 'APPLIED'], 2, 25);

    expect($result->items)->toBe([$trackedJob])
        ->and($result->hasMore)->toBeTrue()
        ->and($repository->lastSearch['ownerId'])->toBe($owner->getId()->toRfc4122())
        ->and($repository->lastSearch['filters'])->toBe(['status' => 'APPLIED'])
        ->and($repository->lastSearch['page'])->toBe(2)
        ->and($repository->lastSearch['pageSize'])->toBe(25);
});


it('exports tracked jobs through the owner-scoped search port', function (): void {
    $repository = new InMemoryTrackedJobRepository();
    $owner = new User(EmailAddress::fromString('owner@example.com'));
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);

    $csv = (new ExportTrackedJobsCsv($repository, new TrackedJobCsvExporter()))->handle(
        $owner,
        new ExportTrackedJobsInput(['company' => 'Acme']),
    );

    expect($csv)->toContain('Id;Company;Title')
        ->and($csv)->toContain($trackedJob->getId()->toRfc4122())
        ->and($repository->lastSearch['ownerId'])->toBe($owner->getId()->toRfc4122())
        ->and($repository->lastSearch['filters'])->toBe(['company' => 'Acme'])
        ->and($repository->lastSearch['page'])->toBe(1)
        ->and($repository->lastSearch['pageSize'])->toBe(5000);
});
it('returns company suggestions through the repository port', function (): void {
    $repository = new InMemoryTrackedJobRepository();
    $repository->companySuggestions = ['Acme', 'Acme Digital', 'Other'];
    $owner = new User(EmailAddress::fromString('owner@example.com'));

    $suggestions = (new SuggestTrackedJobCompanies($repository))->handle($owner, 'ac', 2);

    expect($suggestions)->toBe(['Acme', 'Acme Digital'])
        ->and($repository->lastSearch['ownerId'])->toBe($owner->getId()->toRfc4122())
        ->and($repository->lastSearch['query'])->toBe('ac')
        ->and($repository->lastSearch['limit'])->toBe(2);
});

