<?php

namespace App\TrackedJob\Infrastructure\Doctrine;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

final class TrackedJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackedJob::class);
    }

    public function getByIdForOwner(Uuid $id, User $owner): ?TrackedJob
    {
        return $this->findOneBy(['id' => $id, 'owner' => $owner]);
    }

    /**
     * @return array{items: list<TrackedJob>, total: int}
     */
    public function search(User $owner, array $filters, int $page, int $pageSize): array
    {
        $baseQb = $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->setParameter('owner', $owner);

        $this->applyFilters($baseQb, $filters);

        $countQb = clone $baseQb;
        $total = (int) $countQb
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $finalStatuses = [
            TrackedJobStatus::OFFER_RECEIVED->value,
            TrackedJobStatus::HIRED->value,
            TrackedJobStatus::REJECTED->value,
            TrackedJobStatus::WITHDRAWN->value,
        ];

        $qb = clone $baseQb;
        $qb
            ->addSelect(
                "CASE 
                    WHEN t.status NOT IN (:finalStatuses)
                        AND t.plannedFollowUpDate IS NOT NULL
                        AND t.effectiveFollowUpDate IS NULL
                        AND t.plannedFollowUpDate <= :now
                    THEN 0
                    ELSE 1
                END AS HIDDEN followUpPriority"
            )
            ->addSelect(
                "CASE t.status
                    WHEN 'SECOND_INTERVIEW' THEN 0
                    WHEN 'PRELIMINARY_INTERVIEW' THEN 1
                    WHEN 'FIRST_CONTACT' THEN 2
                    WHEN 'FOLLOW_UP_PENDING' THEN 3
                    WHEN 'FOLLOW_UP_DONE' THEN 4
                    WHEN 'APPLIED' THEN 5
                    WHEN 'DRAFT' THEN 6
                    WHEN 'OFFER_RECEIVED' THEN 7
                    WHEN 'HIRED' THEN 8
                    WHEN 'REJECTED' THEN 9
                    WHEN 'WITHDRAWN' THEN 10
                    ELSE 11
                END AS HIDDEN statusPriority"
            )
            ->addSelect(
                "CASE WHEN t.subjectiveRelevance IS NULL THEN 1 ELSE 0 END AS HIDDEN relevanceNullPriority"
            )
            ->setParameter('finalStatuses', $finalStatuses)
            ->setParameter('now', $now)
            ->orderBy('followUpPriority', 'ASC')
            ->addOrderBy('statusPriority', 'ASC')
            ->addOrderBy('relevanceNullPriority', 'ASC')
            ->addOrderBy('t.subjectiveRelevance', 'DESC')
            ->addOrderBy('t.updatedAt', 'DESC');

        $items = $qb
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * @return list<string>
     */
    public function searchDistinctCompanies(User $owner, string $query, int $limit = 10): array
    {
        $term = '%'.mb_strtolower(trim($query)).'%';

        $rows = $this->createQueryBuilder('t')
            ->select('DISTINCT t.company AS company')
            ->andWhere('t.owner = :owner')
            ->andWhere('t.company IS NOT NULL')
            ->andWhere('LOWER(t.company) LIKE :term')
            ->setParameter('owner', $owner)
            ->setParameter('term', $term)
            ->orderBy('t.company', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_map(static fn (array $row) => $row['company'] ?? null, $rows)));
    }

    public function delete(TrackedJob $trackedJob): void
    {
        $this->getEntityManager()->remove($trackedJob);
    }

    private function applyFilters(\Doctrine\ORM\QueryBuilder $qb, array $filters): void
    {
        if (($filters['company'] ?? null) !== null && $filters['company'] !== '') {
            $qb
                ->andWhere('LOWER(COALESCE(t.company, \'\')) LIKE :company')
                ->setParameter('company', '%'.mb_strtolower(trim($filters['company'])).'%');
        }

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $qb
                ->andWhere('LOWER(COALESCE(t.title, \'\')) LIKE :search OR LOWER(COALESCE(t.company, \'\')) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower(trim($filters['search'])).'%');
        }

        if (($filters['status'] ?? null) instanceof TrackedJobStatus) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']);
        }

        if (($filters['contractType'] ?? null) instanceof ContractType) {
            $qb->andWhere('t.contractType = :contractType')->setParameter('contractType', $filters['contractType']);
        }

        if (($filters['remoteMode'] ?? null) instanceof RemoteMode) {
            $qb->andWhere('t.remoteMode = :remoteMode')->setParameter('remoteMode', $filters['remoteMode']);
        }
    }
}
