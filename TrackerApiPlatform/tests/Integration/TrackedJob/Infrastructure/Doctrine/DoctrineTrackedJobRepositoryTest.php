<?php

declare(strict_types=1);

/*
 * Database-backed integration tests for the tracked job Doctrine repository.
 * They run when PostgreSQL is available and otherwise skip cleanly on local PHP installations without pdo_pgsql.
 */

use App\Security\Domain\ValueObject\UserId;
use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

it('persists, loads and owner-filters tracked jobs through Doctrine', function (): void {
    self::bootKernel();

    /** @var EntityManagerInterface $entityManager */
    $entityManager = self::getContainer()->get(EntityManagerInterface::class);
    ensureTrackedJobDatabaseAvailable($entityManager);

    $repository = self::getContainer()->get(TrackedJobRepositoryInterface::class);
    $owner = persistedTrackedJobOwner($entityManager, '018f6d6f-0000-7000-8000-000000000101', 'owner-p12@example.com');
    $otherOwner = persistedTrackedJobOwner($entityManager, '018f6d6f-0000-7000-8000-000000000102', 'other-p12@example.com');
    $trackedJob = TrackedJob::openFor(UserId::fromString($owner->getId()->toRfc4122()));
    $trackedJob->updatePosition(
        CompanyName::fromNullable('Acme'),
        JobTitle::fromNullable('Backend Engineer'),
        ContractType::CDD,
        'Paris',
        RemoteMode::HYBRID,
        '60k',
        null,
        null,
    );

    $repository->save($trackedJob);
    $entityManager->flush();

    expect($repository->getByIdForOwner($trackedJob->getId(), $trackedJob->ownerId()))->toBeInstanceOf(TrackedJob::class)
        ->and($repository->getByIdForOwner($trackedJob->getId(), UserId::fromString($otherOwner->getId()->toRfc4122())))
        ->toBeNull();

    $repository->remove($trackedJob);
    $entityManager->remove($owner);
    $entityManager->remove($otherOwner);
    $entityManager->flush();
});

it('searches and suggests companies through Doctrine filters', function (): void {
    self::bootKernel();

    /** @var EntityManagerInterface $entityManager */
    $entityManager = self::getContainer()->get(EntityManagerInterface::class);
    ensureTrackedJobDatabaseAvailable($entityManager);

    $repository = self::getContainer()->get(TrackedJobRepositoryInterface::class);
    $owner = persistedTrackedJobOwner($entityManager, '018f6d6f-0000-7000-8000-000000000103', 'search-p12@example.com');
    $ownerId = UserId::fromString($owner->getId()->toRfc4122());
    $trackedJob = TrackedJob::openFor($ownerId);
    $trackedJob->updatePosition(
        CompanyName::fromNullable('Acme Search'),
        JobTitle::fromNullable('Backend Engineer'),
        ContractType::CDD,
        'Paris',
        RemoteMode::HYBRID,
        null,
        null,
        null,
    );
    $trackedJob->updateTimeline(TrackedJobTimeline::fromProcessDates(
        new \DateTimeImmutable('2026-04-01'),
        null,
        null,
        null,
        null,
    ));

    $repository->save($trackedJob);
    $entityManager->flush();

    $result = $repository->search($ownerId, ['company' => 'acme', 'contractType' => ContractType::CDD], 1, 10);

    expect($result['items'])->toHaveCount(1)
        ->and($result['items'][0])->toBeInstanceOf(TrackedJob::class)
        ->and($repository->searchDistinctCompanies($ownerId, 'acm', 5))->toBe(['Acme Search']);

    $repository->remove($trackedJob);
    $entityManager->remove($owner);
    $entityManager->flush();
});

function ensureTrackedJobDatabaseAvailable(EntityManagerInterface $entityManager): void
{
    if (!extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql is required for tracked job repository integration tests.');
    }

    try {
        $entityManager->getConnection()->executeQuery('SELECT 1');
    } catch (DbalException $exception) {
        test()->markTestSkipped('PostgreSQL test database is not available: '.$exception->getMessage());
    }
}

function persistedTrackedJobOwner(EntityManagerInterface $entityManager, string $id, string $email): UserRecord
{
    $record = $entityManager->find(UserRecord::class, $id);

    if ($record instanceof UserRecord) {
        return $record;
    }

    $record = new UserRecord();
    $record->setId(Uuid::fromString($id));
    $record->setEmail($email);
    $record->setNormalizedEmail($email);
    $record->setRoles(['ROLE_USER']);
    $record->setPasswordHash('hash');
    $record->setCreatedAt(new \DateTimeImmutable('2026-04-01T00:00:00+00:00'));
    $entityManager->persist($record);
    $entityManager->flush();

    return $record;
}
