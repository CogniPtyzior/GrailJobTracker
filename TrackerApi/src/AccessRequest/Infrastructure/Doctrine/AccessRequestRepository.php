<?php

namespace App\AccessRequest\Infrastructure\Doctrine;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

final class AccessRequestRepository extends ServiceEntityRepository implements AccessRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessRequest::class);
    }

    public function getById(Uuid $id): ?AccessRequest
    {
        return $this->find($id);
    }

    /**
     * @return array{items: list<AccessRequest>, total: int}
     */
    public function search(?string $query, int $page, int $pageSize): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($query !== null && $query !== '') {
            $term = '%'.mb_strtolower(trim($query)).'%';
            $qb
                ->andWhere('LOWER(r.email) LIKE :term OR LOWER(r.companyName) LIKE :term OR LOWER(r.reason) LIKE :term')
                ->setParameter('term', $term);
        }

        $qb
            ->orderBy('r.createdAt', 'DESC');

        $countQb = clone $qb;
        $countQb->resetDQLPart('orderBy');
        $total = (int) $countQb
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

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
}
