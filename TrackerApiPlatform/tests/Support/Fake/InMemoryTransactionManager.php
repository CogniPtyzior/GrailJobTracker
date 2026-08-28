<?php

declare(strict_types=1);

namespace App\Tests\Support\Fake;

use App\Shared\Application\Transaction\TransactionManagerInterface;

final class InMemoryTransactionManager implements TransactionManagerInterface
{
    public int $transactionCalls = 0;

    public function transactional(callable $operation): mixed
    {
        ++$this->transactionCalls;

        return $operation();
    }
}
