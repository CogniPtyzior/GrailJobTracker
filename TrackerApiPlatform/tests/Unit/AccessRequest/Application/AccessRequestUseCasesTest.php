<?php

declare(strict_types=1);

/*
 * Unit tests for access request application use cases.
 * They verify orchestration through repository and notification ports without Doctrine, Messenger or API Platform.
 */

use App\AccessRequest\Application\Input\CreateAccessRequestInput;
use App\AccessRequest\Application\UseCase\CreateAccessRequest;
use App\AccessRequest\Application\UseCase\GetAccessRequest;
use App\AccessRequest\Application\UseCase\SearchAccessRequests;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use App\Tests\Support\Fake\FakeAccessRequestNotificationDispatcher;
use App\Tests\Support\Fake\InMemoryAccessRequestRepository;

it('creates an access request and dispatches a created notification', function (): void {
    $repository = new InMemoryAccessRequestRepository();
    $dispatcher = new FakeAccessRequestNotificationDispatcher();

    $accessRequest = (new CreateAccessRequest($repository, $dispatcher))->handle(new CreateAccessRequestInput(
        email: '  JOHN@example.com  ',
        companyName: AccessRequestCompanyName::fromString('  Acme  '),
        reason: AccessRequestReason::fromString('  I need access to manage jobs.  '),
        firstName: PersonName::fromNullable('  John  '),
        lastName: PersonName::fromNullable('   '),
    ));

    expect($accessRequest->getEmail())->toBe('  JOHN@example.com  ')
        ->and($accessRequest->getNormalizedEmail())->toBe('john@example.com')
        ->and($accessRequest->getCompanyName())->toBe('Acme')
        ->and($accessRequest->reason()->value())->toBe('I need access to manage jobs.')
        ->and($accessRequest->firstName()?->value())->toBe('John')
        ->and($accessRequest->lastName())->toBeNull()
        ->and($repository->getById($accessRequest->getId()))->toBe($accessRequest)
        ->and($repository->saveCalls)->toBe(1)
        ->and($repository->flushCalls)->toBe(1)
        ->and($dispatcher->createdNotifications)->toBe([$accessRequest]);
});

it('loads an access request by id through the repository port', function (): void {
    $accessRequest = accessRequestFixture('john@example.com', 'Acme');
    $repository = new InMemoryAccessRequestRepository([$accessRequest]);

    $result = (new GetAccessRequest($repository))->handle($accessRequest->getId());

    expect($result)->toBe($accessRequest);
});

it('returns null when an access request cannot be found', function (): void {
    $repository = new InMemoryAccessRequestRepository();

    $result = (new GetAccessRequest($repository))->handle(accessRequestFixture('john@example.com', 'Acme')->getId());

    expect($result)->toBeNull();
});

it('searches access requests through the repository port', function (): void {
    $matching = accessRequestFixture('ada@example.com', 'Acme Labs', 'I need access to review applications.');
    $other = accessRequestFixture('grace@example.com', 'Other', 'I need access for another team.');
    $repository = new InMemoryAccessRequestRepository([$matching, $other]);

    $result = (new SearchAccessRequests($repository))->handle('acme', 1, 10);

    expect($result->items)->toBe([$matching])
        ->and($result->total)->toBe(1)
        ->and($repository->lastSearch['query'])->toBe('acme')
        ->and($repository->lastSearch['page'])->toBe(1)
        ->and($repository->lastSearch['pageSize'])->toBe(10);
});

function accessRequestFixture(
    string $email,
    string $companyName,
    string $reason = 'I need access to manage jobs.',
): AccessRequest {
    return AccessRequest::submit(
        EmailAddress::fromString($email),
        AccessRequestCompanyName::fromString($companyName),
        AccessRequestReason::fromString($reason),
        PersonName::fromNullable('Ada'),
        PersonName::fromNullable('Lovelace'),
    );
}
