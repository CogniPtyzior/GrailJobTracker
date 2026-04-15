<?php

namespace App\Security\Infrastructure\Doctrine;

use App\Security\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByNormalizedEmail(string $normalizedEmail): ?User
    {
        return $this->findOneBy(['normalizedEmail' => $normalizedEmail]);
    }

    /**
     * @return array{items: list<User>, total: int}
     */
    public function search(?bool $isActive, ?string $query, int $page, int $pageSize): array
    {
        $qb = $this->createQueryBuilder('u');

        if ($isActive !== null) {
            $qb->andWhere('u.isActive = :isActive')->setParameter('isActive', $isActive);
        }

        if ($query !== null && $query !== '') {
            $term = '%'.mb_strtolower(trim($query)).'%';
            $qb
                ->andWhere('LOWER(u.email) LIKE :term OR LOWER(COALESCE(u.firstName, \'\')) LIKE :term OR LOWER(COALESCE(u.lastName, \'\')) LIKE :term')
                ->setParameter('term', $term);
        }

        $qb
            ->orderBy('u.isActive', 'DESC')
            ->addOrderBy('u.createdAt', 'DESC');

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(u.id)')
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

    public function getById(Uuid $id): ?User
    {
        return $this->find($id);
    }
}
