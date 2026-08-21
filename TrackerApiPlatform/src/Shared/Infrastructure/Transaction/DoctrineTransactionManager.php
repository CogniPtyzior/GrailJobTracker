<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transaction;

use App\Shared\Application\Transaction\TransactionManagerInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function transactional(callable $operation): mixed
    {
        // @phpstan-ignore argument.templateType
        return $this->connection->transactional($operation);
    }
}
