<?php

declare(strict_types=1);

/*
 * Integration tests for the access request Doctrine adapter wiring.
 * They verify mapping configuration and repository aliasing without requiring persisted rows.
 */

use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Infrastructure\Doctrine\Entity\AccessRequestRecord;
use App\AccessRequest\Infrastructure\Doctrine\Repository\DoctrineAccessRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

it('registers access request records in Doctrine metadata', function (): void {
    self::bootKernel();

    /** @var EntityManagerInterface $entityManager */
    $entityManager = self::getContainer()->get(EntityManagerInterface::class);
    $metadata = $entityManager->getClassMetadata(AccessRequestRecord::class);

    expect($metadata->getTableName())->toBe('access_requests')
        ->and($metadata->getSchemaName())->toBe('trackers')
        ->and($metadata->getColumnNames())->toContain(
            'normalized_email',
            'company_name',
            'reason',
            'first_name',
            'last_name',
            'created_at',
        );
});

it('aliases the access request repository port to the Doctrine adapter', function (): void {
    self::bootKernel();

    $repository = self::getContainer()->get(AccessRequestRepositoryInterface::class);

    expect($repository)->toBeInstanceOf(DoctrineAccessRequestRepository::class);
});
