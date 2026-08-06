<?php

namespace App\AccessRequest\Infrastructure\Doctrine\Repository;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Infrastructure\Doctrine\Entity\AccessRequestRecord;
use App\AccessRequest\Infrastructure\Doctrine\Mapper\AccessRequestRecordMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;

final class DoctrineAccessRequestRepository extends ServiceEntityRepository implements AccessRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, private readonly AccessRequestRecordMapper $mapper)
    {
        parent::__construct($registry, AccessRequestRecord::class);
    }

    public function getById(AccessRequestId $id): ?AccessRequest
    {
        $record = $this->find($id->toUuid());

        return $record instanceof AccessRequestRecord ? $this->mapper->toDomain($record) : null;
    }

    public function search(?string $query, int $page, int $pageSize): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($query !== null && $query !== '') {
            $term = '%'.mb_strtolower(trim($query)).'%';
            $qb
                ->andWhere('LOWER(r.email) LIKE :term OR LOWER(r.companyName) LIKE :term OR LOWER(r.reason) LIKE :term')
                ->setParameter('term', $term);
        }

        $qb->orderBy('r.createdAt', 'DESC');

        $countQb = clone $qb;
        $countQb->resetDQLPart('orderBy');
        $total = (int) $countQb->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();

        $records = $qb
            ->setFirstResult(($page - 1) * $pageSize)
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
        $record = $this->find($accessRequest->getId()->toUuid()) ?? new AccessRequestRecord();
        $this->mapper->updateRecord($accessRequest, $record);
        $this->getEntityManager()->persist($record);
    }

    public function remove(AccessRequest $accessRequest): void
    {
        $record = $this->find($accessRequest->getId()->toUuid());

        if ($record instanceof AccessRequestRecord) {
            $this->getEntityManager()->remove($record);
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
