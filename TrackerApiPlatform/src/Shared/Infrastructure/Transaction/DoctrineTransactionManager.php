<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transaction;

use App\Shared\Application\Transaction\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function transactional(callable $operation): mixed
    {
        // @phpstan-ignore argument.templateType
        return $this->entityManager->wrapInTransaction($operation);
    }
}
