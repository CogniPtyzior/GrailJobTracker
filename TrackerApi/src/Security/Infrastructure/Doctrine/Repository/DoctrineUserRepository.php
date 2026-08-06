<?php

namespace App\Security\Infrastructure\Doctrine\Repository;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
use App\Security\Infrastructure\Doctrine\Mapper\UserRecordMapper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

final class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, private readonly UserRecordMapper $mapper)
    {
        parent::__construct($registry, UserRecord::class);
    }

    public function findOneByNormalizedEmail(string $normalizedEmail): ?User
    {
        $record = $this->findOneBy(['normalizedEmail' => $normalizedEmail]);

        return $record instanceof UserRecord ? $this->mapper->toDomain($record) : null;
    }

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

        $qb->orderBy('u.isActive', 'DESC')->addOrderBy('u.createdAt', 'DESC');

        $countQb = clone $qb;
        $countQb->resetDQLPart('orderBy');
        $total = (int) $countQb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

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

    public function getById(Uuid $id): ?User
    {
        $record = $this->find($id);

        return $record instanceof UserRecord ? $this->mapper->toDomain($record) : null;
    }

    public function save(User $user): void
    {
        $record = $this->find($user->getId()) ?? new UserRecord();
        $this->mapper->updateRecord($user, $record);
        $this->getEntityManager()->persist($record);
    }

    public function remove(User $user): void
    {
        $record = $this->find($user->getId());

        if ($record instanceof UserRecord) {
            $this->getEntityManager()->remove($record);
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}