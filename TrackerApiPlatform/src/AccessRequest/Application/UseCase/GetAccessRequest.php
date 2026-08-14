<?php

declare(strict_types=1);

/*
 * Application use case that retrieves an access request by id.
 * Admin API processors will use it before applying approval or deletion workflows.
 */

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;

final readonly class GetAccessRequest
{
    public function __construct(private AccessRequestRepositoryInterface $accessRequestRepository)
    {
    }

    public function handle(AccessRequestId $id): ?AccessRequest
    {
        return $this->accessRequestRepository->getById($id);
    }
}
