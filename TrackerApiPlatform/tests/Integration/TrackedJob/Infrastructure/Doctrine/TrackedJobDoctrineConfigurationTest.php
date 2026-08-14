<?php

declare(strict_types=1);

/*
 * Integration tests for the tracked job Doctrine adapter wiring.
 * They verify mapping configuration and repository aliasing without requiring database rows.
 */

use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;
use App\TrackedJob\Infrastructure\Doctrine\Entity\TrackedJobRecord;
use App\TrackedJob\Infrastructure\Doctrine\Repository\DoctrineTrackedJobRepository;
use Doctrine\ORM\EntityManagerInterface;

it('registers tracked job records in Doctrine metadata', function (): void {
    self::bootKernel();

    /** @var EntityManagerInterface $entityManager */
    $entityManager = self::getContainer()->get(EntityManagerInterface::class);
    $metadata = $entityManager->getClassMetadata(TrackedJobRecord::class);

    expect($metadata->getTableName())->toBe('tracked_jobs')
        ->and($metadata->getSchemaName())->toBe('trackers')
        ->and($metadata->getColumnNames())->toContain(
            'contract_type',
            'remote_mode',
            'application_date',
            'planned_follow_up_date',
            'subjective_relevance',
        );
});

it('aliases the tracked job repository port to the Doctrine adapter', function (): void {
    self::bootKernel();

    $repository = self::getContainer()->get(TrackedJobRepositoryInterface::class);

    expect($repository)->toBeInstanceOf(DoctrineTrackedJobRepository::class);
});
