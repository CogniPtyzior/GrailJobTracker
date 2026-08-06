<?php

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;

/**
 * Application use case that retrieves an access request by id for admin operations.
 */
final class GetAccessRequest
{
    public function __construct(
        private readonly AccessRequestRepositoryInterface $accessRequestRepository,
    ) {
    }

    public function handle(AccessRequestId $id): ?AccessRequest
    {
        return $this->accessRequestRepository->getById($id);
    }
}
