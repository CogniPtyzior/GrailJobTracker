<?php

namespace App\TrackedJob\Infrastructure\Doctrine\Repository;

use App\Security\Domain\ValueObject\UserId;
use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;
use App\TrackedJob\Infrastructure\Doctrine\Entity\TrackedJobRecord;
use App\TrackedJob\Infrastructure\Doctrine\Mapper\TrackedJobRecordMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;

final class DoctrineTrackedJobRepository extends ServiceEntityRepository implements TrackedJobRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, private readonly TrackedJobRecordMapper $mapper)
    {
        parent::__construct($registry, TrackedJobRecord::class);
    }

    public function getByIdForOwner(TrackedJobId $id, UserId $ownerId): ?TrackedJob
    {
        $ownerRecord = $this->findUserRecord($ownerId);

        if (!$ownerRecord instanceof UserRecord) {
            return null;
        }

        $record = $this->findOneBy(['id' => $id->toUuid(), 'owner' => $ownerRecord]);

        return $record instanceof TrackedJobRecord ? $this->mapper->toDomain($record) : null;
    }

    public function search(UserId $ownerId, array $filters, int $page, int $pageSize): array
    {
        $ownerRecord = $this->findUserRecord($ownerId);

        if (!$ownerRecord instanceof UserRecord) {
            return ['items' => [], 'hasMore' => false];
        }

        $baseQb = $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $ownerRecord);

        $this->applyFilters($baseQb, $filters);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $finalStatuses = [
            TrackedJobStatus::OFFER_RECEIVED->value,
            TrackedJobStatus::HIRED->value,
            TrackedJobStatus::REJECTED->value,
            TrackedJobStatus::WITHDRAWN->value,
        ];

        $qb = clone $baseQb;
        $qb
            ->addSelect("CASE WHEN t.status NOT IN (:finalStatuses) AND t.plannedFollowUpDate IS NOT NULL AND t.effectiveFollowUpDate IS NULL AND t.plannedFollowUpDate <= :now THEN 0 ELSE 1 END AS HIDDEN followUpPriority")
            ->addSelect("CASE t.status WHEN 'SECOND_INTERVIEW' THEN 0 WHEN 'PRELIMINARY_INTERVIEW' THEN 1 WHEN 'FIRST_CONTACT' THEN 2 WHEN 'FOLLOW_UP_PENDING' THEN 3 WHEN 'FOLLOW_UP_DONE' THEN 4 WHEN 'APPLIED' THEN 5 WHEN 'DRAFT' THEN 6 WHEN 'OFFER_RECEIVED' THEN 7 WHEN 'HIRED' THEN 8 WHEN 'REJECTED' THEN 9 WHEN 'WITHDRAWN' THEN 10 ELSE 11 END AS HIDDEN statusPriority")
            ->addSelect("CASE WHEN t.subjectiveRelevance IS NULL THEN 1 ELSE 0 END AS HIDDEN relevanceNullPriority")
            ->setParameter('finalStatuses', $finalStatuses)
            ->setParameter('now', $now)
            ->orderBy('followUpPriority', 'ASC')
            ->addOrderBy('statusPriority', 'ASC')
            ->addOrderBy('relevanceNullPriority', 'ASC')
            ->addOrderBy('t.subjectiveRelevance', 'DESC')
            ->addOrderBy('t.updatedAt', 'DESC');

        $records = $qb
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize + 1)
            ->getQuery()
            ->getResult();

        $hasMore = count($records) > $pageSize;
        $records = $hasMore ? array_slice($records, 0, $pageSize) : $records;

        return [
            'items' => array_map($this->mapper->toDomain(...), $records),
            'hasMore' => $hasMore,
        ];
    }

    public function searchDistinctCompanies(UserId $ownerId, string $query, int $limit = 10): array
    {
        $ownerRecord = $this->findUserRecord($ownerId);

        if (!$ownerRecord instanceof UserRecord) {
            return [];
        }

        $term = '%'.mb_strtolower(trim($query)).'%';
        $companies = $this->createQueryBuilder('t')
            ->select('DISTINCT t.company')
            ->andWhere('t.owner = :owner')
            ->andWhere('t.company IS NOT NULL')
            ->andWhere('LOWER(t.company) LIKE :term')
            ->setParameter('owner', $ownerRecord)
            ->setParameter('term', $term)
            ->orderBy('t.company', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        /** @var list<string> $companies */
        return $companies;
    }

    public function save(TrackedJob $trackedJob): void
    {
        $ownerRecord = $this->findUserRecord($trackedJob->ownerId());

        if (!$ownerRecord instanceof UserRecord) {
            throw new \RuntimeException('Tracked job owner must be persisted before the tracked job can be saved.');
        }

        $record = $this->find($trackedJob->getId()->toUuid()) ?? new TrackedJobRecord();
        $this->mapper->updateRecord($trackedJob, $record, $ownerRecord);
        $this->getEntityManager()->persist($record);
    }

    public function delete(TrackedJob $trackedJob): void
    {
        $this->remove($trackedJob);
    }

    public function remove(TrackedJob $trackedJob): void
    {
        $record = $this->find($trackedJob->getId()->toUuid());

        if ($record instanceof TrackedJobRecord) {
            $this->getEntityManager()->remove($record);
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    private function findUserRecord(UserId $ownerId): ?UserRecord
    {
        return $this->getEntityManager()->find(UserRecord::class, $ownerId->toUuid());
    }

    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, array $filters): void
    {
        if (($filters['statusInvalid'] ?? false) || ($filters['contractTypeInvalid'] ?? false) || ($filters['remoteModeInvalid'] ?? false)) {
            $qb->andWhere('1 = 0');
            return;
        }

        if (($filters['company'] ?? null) !== null && $filters['company'] !== '') {
            $qb->andWhere('LOWER(t.company) LIKE :company')->setParameter('company', '%'.mb_strtolower(trim($filters['company'])).'%');
        }

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $qb->andWhere('(LOWER(t.title) LIKE :search OR LOWER(t.company) LIKE :search)')->setParameter('search', '%'.mb_strtolower(trim($filters['search'])).'%');
        }

        if (($filters['status'] ?? null) instanceof TrackedJobStatus) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']->value);
        }

        if (($filters['contractType'] ?? null) instanceof ContractType) {
            $qb->andWhere('t.contractType = :contractType')->setParameter('contractType', $filters['contractType']->value);
        }

        if (($filters['remoteMode'] ?? null) instanceof RemoteMode) {
            $qb->andWhere('t.remoteMode = :remoteMode')->setParameter('remoteMode', $filters['remoteMode']->value);
        }
    }
}
