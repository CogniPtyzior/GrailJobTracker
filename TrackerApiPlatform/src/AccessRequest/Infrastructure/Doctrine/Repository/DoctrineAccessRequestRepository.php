<?php

declare(strict_types=1);

/*
 * Doctrine adapter for the access request repository port.
 * It persists public access requests while returning domain aggregates to application code.
 */

namespace App\AccessRequest\Infrastructure\Doctrine\Repository;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Infrastructure\Doctrine\Entity\AccessRequestRecord;
use App\AccessRequest\Infrastructure\Doctrine\Mapper\AccessRequestRecordMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessRequestRecord>
 */
final class DoctrineAccessRequestRepository extends ServiceEntityRepository implements AccessRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, private readonly AccessRequestRecordMapper $mapper)
    {
        parent::__construct($registry, AccessRequestRecord::class);
    }

    public function getById(AccessRequestId $id): ?AccessRequest
    {
        $record = $this->find($id->toRfc4122());

        return $record instanceof AccessRequestRecord ? $this->mapper->toDomain($record) : null;
    }

    public function search(?string $query, int $page, int $pageSize): array
    {
        $queryBuilder = $this->createQueryBuilder('request');

        if ($query !== null && trim($query) !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(request.email) LIKE :term'
                    .' OR LOWER(request.companyName) LIKE :term'
                    .' OR LOWER(request.reason) LIKE :term',
                )
                ->setParameter('term', '%'.mb_strtolower(trim($query)).'%');
        }

        $queryBuilder->orderBy('request.createdAt', 'DESC');

        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->resetDQLPart('orderBy');
        $total = (int) $countQueryBuilder->select('COUNT(request.id)')->getQuery()->getSingleScalarResult();

        $records = $queryBuilder
            ->setFirstResult((max(1, $page) - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        return [
            'items' => array_map($this->mapper->toDomain(...), $records),
            'total' => $total,
        ];
    }

    public function save(AccessRequest $accessRequest): void
    {
        $record = $this->find($accessRequest->getId()->toRfc4122()) ?? new AccessRequestRecord();
        $this->mapper->updateRecord($accessRequest, $record);
        $this->getEntityManager()->persist($record);
    }

    public function remove(AccessRequest $accessRequest): void
    {
        $record = $this->find($accessRequest->getId()->toRfc4122());

        if ($record instanceof AccessRequestRecord) {
            $this->getEntityManager()->remove($record);
        }
    }

}
