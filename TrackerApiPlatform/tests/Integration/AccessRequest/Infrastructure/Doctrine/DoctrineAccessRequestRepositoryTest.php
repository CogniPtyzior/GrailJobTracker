<?php

declare(strict_types=1);

/*
 * Database-backed integration tests for the access request Doctrine repository.
 * They run when PostgreSQL is available and keep cleanup scoped to deterministic P18 test emails.
 */

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;

it('persists, loads and removes access requests through Doctrine', function (): void {
    self::bootKernel();

    /** @var EntityManagerInterface $entityManager */
    $entityManager = self::getContainer()->get(EntityManagerInterface::class);
    ensureAccessRequestDatabaseAvailable($entityManager);
    purgeP18AccessRequests($entityManager);

    $repository = self::getContainer()->get(AccessRequestRepositoryInterface::class);
    $accessRequest = doctrineAccessRequestFixture(
        '018f6d6f-0000-7000-8000-000000000401',
        'p18-load@example.com',
        'Acme Repository',
        'This request should be persisted and loaded by the Doctrine adapter.',
    );

    $repository->save($accessRequest);
    $repository->flush();

    $loaded = $repository->getById($accessRequest->getId());

    expect($loaded)->toBeInstanceOf(AccessRequest::class)
        ->and($loaded?->getEmail())->toBe('p18-load@example.com')
        ->and($loaded?->companyName()->value())->toBe('Acme Repository');

    $repository->remove($accessRequest);
    $repository->flush();

    expect($repository->getById($accessRequest->getId()))->toBeNull();

    purgeP18AccessRequests($entityManager);
});

it('searches access requests through Doctrine with total count', function (): void {
    self::bootKernel();

    /** @var EntityManagerInterface $entityManager */
    $entityManager = self::getContainer()->get(EntityManagerInterface::class);
    ensureAccessRequestDatabaseAvailable($entityManager);
    purgeP18AccessRequests($entityManager);

    $repository = self::getContainer()->get(AccessRequestRepositoryInterface::class);
    $olderRequest = doctrineAccessRequestFixture(
        '018f6d6f-0000-7000-8000-000000000402',
        'p18-old@example.com',
        'Acme Search',
        'This request should match the repository search by company name.',
        '2026-04-01T10:00:00+00:00',
    );
    $newerRequest = doctrineAccessRequestFixture(
        '018f6d6f-0000-7000-8000-000000000403',
        'p18-new@example.com',
        'Acme Search',
        'This request should be returned first because it is newer.',
        '2026-04-02T10:00:00+00:00',
    );

    $repository->save($olderRequest);
    $repository->save($newerRequest);
    $repository->flush();

    $result = $repository->search('acme', 1, 10);

    expect($result['total'])->toBe(2)
        ->and($result['items'])->toHaveCount(2)
        ->and($result['items'][0]->getId()->equals($newerRequest->getId()))->toBeTrue()
        ->and($result['items'][1]->getId()->equals($olderRequest->getId()))->toBeTrue();

    purgeP18AccessRequests($entityManager);
});

function ensureAccessRequestDatabaseAvailable(EntityManagerInterface $entityManager): void
{
    if (!extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql is required for access request repository integration tests.');
    }

    try {
        $entityManager->getConnection()->executeQuery('SELECT 1');
    } catch (DbalException $exception) {
        test()->markTestSkipped('PostgreSQL test database is not available: '.$exception->getMessage());
    }
}

function purgeP18AccessRequests(EntityManagerInterface $entityManager): void
{
    $entityManager->getConnection()->executeStatement(
        "DELETE FROM trackers.access_requests WHERE normalized_email LIKE 'p18-%@example.com'",
    );
    $entityManager->clear();
}

function doctrineAccessRequestFixture(
    string $id,
    string $email,
    string $companyName,
    string $reason,
    string $createdAt = '2026-04-01T10:00:00+00:00',
): AccessRequest {
    return AccessRequest::reconstitute(
        AccessRequestId::fromString($id),
        EmailAddress::fromString($email),
        AccessRequestCompanyName::fromString($companyName),
        AccessRequestReason::fromString($reason),
        PersonName::fromNullable('Jane'),
        PersonName::fromNullable('Doe'),
        new DateTimeImmutable($createdAt),
    );
}
