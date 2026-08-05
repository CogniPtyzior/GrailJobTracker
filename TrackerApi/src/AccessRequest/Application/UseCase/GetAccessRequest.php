<?php

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Application use case that retrieves an access request by id for admin operations.
 */
final class GetAccessRequest
{
    public function __construct(
        private readonly AccessRequestRepositoryInterface $accessRequestRepository,
    ) {
    }

    public function handle(Uuid $id): ?AccessRequest
    {
        return $this->accessRequestRepository->getById($id);
    }
}